<?php

require_once 'abstract.php';

/**
 * @author Matthias Kerstner <matthias@both-interact.com>
 * @version 1.0.0
 * @copyright (c) 2015, Both Interact GmbH
 */
class Mage_Shell_Clean_Product_Texts extends Mage_Shell_Abstract {

    const PAGE_SIZE = 100;

    /**
     * Parse $string with comma separated values and return array.
     *
     * @param string $string
     * @return array
     */
    protected function _parseString($string) {
        $values = array();
        if (!empty($string)) {
            $values = explode(',', $string);
            $values = array_map('trim', $values);
        }
        return $values;
    }

    /**
     * Run script based on CL options specified.
     */
    public function run() {
        if ($this->getArg('products')) {

            // switch to admin event area
            Mage::app()->addEventArea('admin');

            // product model observer to be called on products
            $productModelObserver = new BothInteract_CleanProductTexts_Model_Observer();

            // allowed attribute types
            $types = array('varchar', 'text', 'decimal', 'datetime', 'int');

            // attribute sets array
            $attributeSets = array();

            // user defined attribute ids
            $entityType = Mage::getModel('eav/entity_type')
                    ->loadByCode('catalog_product');

            $attributeCollection = $entityType
                    ->getAttributeCollection()
                    ->addFilter('is_user_defined', '1')
                    ->getItems();
            $attrIds = array();
            foreach ($attributeCollection as $attribute) {
                $attrIds[] = $attribute->getId();
            }
            $userDefined = implode(',', $attrIds);

            // product collection based on attribute filters
            $collection = Mage::getModel('catalog/product')->getCollection();
            $entityTable = $collection
                    ->getTable(Mage::getModel('eav/entity_type')
                    ->loadByCode('catalog_product')
                    ->getEntityTable());

            // load specific product_types (currently supported: simple, configurable)
            $cliTypes = $this->getArg('types');
            if (!empty($cliTypes) && $cliTypes != 'all') {
                $productTypes = $this->_parseString($this->getArg('types'));

                foreach ($productTypes as $k => $productType) {
                    if (!in_array($productType, array('simple', 'configurable'))) {
                        unset($productTypes[$k]);
                    }
                }
                $collection->addAttributeToFilter('type_id', array('in' => $productTypes));
            }

            // load product IDs specified only
            if ($this->getArg('products') != 'all') {
                if ($ids = $this->_parseString($this->getArg('products'))) {
                    $collection->addAttributeToFilter('entity_id', array('in' => $ids));
                }
            }

            $collection->setPageSize(self::PAGE_SIZE);

            $pages = $collection->getLastPageNumber();
            $currentPage = 1;

            //light product collection iterating
            while ($currentPage <= $pages) {

                echo 'Processing products page ' . $currentPage . ' of ' . $pages
                . '...' . PHP_EOL;

                $collection->setCurPage($currentPage);
                $collection->load();

                foreach ($collection->getItems() as $item) {

                    // load product to manipulate
                    $product = Mage::getModel('catalog/product')
                            ->load($item->getId());

                    // manually call our observer to update child products
                    $productModelObserver->processProduct($product);
                }

                $currentPage++;
                $collection->clear();
            }

            echo 'Done!' . PHP_EOL;
        } else {
            echo $this->usageHelp();
        }
    }

    /**
     * Retrieve Usage Help Message.
     */
    public function usageHelp() {
        return <<<USAGE
 
Usage:  php -f clean_product_texts -- [options]
 
    --products all              Clean all products
    --products <product_ids>    Clean products by IDs
    --types all                  Clean all product types
    --types <product_types>      Clean only product types specified
    help                        Show this help
 
    <product_ids>               Comma separated IDs of products
    <product_types>             Comma separated list of product types: simple and/or configurable
 
USAGE;
    }

}

$shell = new Mage_Shell_Clean_Product_Texts();
$shell->run();
