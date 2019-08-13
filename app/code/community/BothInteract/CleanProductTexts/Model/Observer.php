<?php

/**
 * Handles product cleaning based on configuration set in backend.
 * 
 * This class writes log messages to a custom log file specified by 
 * @see self::$LOG_FILE.
 *  
 * @author Matthias Kerstner <matthias@both-interact.com>
 * @version 1.0.0
 * @copyright (c) 2015, Both Interact GmbH
 */
class BothInteract_CleanProductTexts_Model_Observer {

    /** @var boolean flag to avoid recursion when saving products in this observer */
    private $_isProcessed = false;

    /** @var string this module's namespace */
    private static $_MODULE_NAMESPACE = 'bothinteract_cleanproducttexts';

    /**
     * Logs $msg to logfile specified in configuration.
     * @param string $msg
     */
    private function logToFile($msg) {
        Mage::log($msg, null, Mage::getStoreConfig(
                        self::$_MODULE_NAMESPACE
                        . '/general/log_file', Mage::app()->getStore()));
    }

    /**
     * Handles product based on $cleaningOptions specified.
     * @param Mage_Catalog_Model_Product $product
     * @var array $cleaningOptions
     */
    private function handleProduct(Mage_Catalog_Model_Product $product, $cleaningOptions) {
        $this->logToFile('Handling '
                . mb_strtoupper($product->getTypeId())
                . ' product ' . $product->getId());

        foreach ($cleaningOptions as $cleaningOption) {
            $this->logToFile('Checking option ' . $cleaningOption);

            // remove control characters for description and short description
            if ($cleaningOption == BothInteract_CleanProductTexts_Model_System_Config_Source_View::$VALUE_CLEANING_TYPE_CONTROL_CHARACTERS) {
                // @see http://unicode-table.com/en/
                $this->logToFile('Cleaning product ' . $product->getId());

                $regexp = '/[^\PC\s]/u'; //i.e. /[\x00—\x1F\x80-\x9f]
                $product->setDescription(preg_replace('/\x0b/', '', preg_replace($regexp, '', $product->getDescription())));
                $product->setShortDescription(preg_replace('/\x0b/', '', preg_replace($regexp, '', $product->getShortDescription())));
            }
        }

        if (Mage::getStoreConfig(self::$_MODULE_NAMESPACE
                        . '/general/is_simulation')) {
            $this->logToFile('************************************');
            $this->logToFile('SIMULATION: Not saving product '
                    . $product->getId());
            $this->logToFile('************************************');
        } else {
            $this->logToFile('Saving product ' . $product->getId());
            $product->save();
        }

        $this->logToFile('Successfully handled product ' . $product->getId());
    }

    /**
     * Handles product save event calls by checking product type.
     * @param Varien_Event_Observer $observer
     */
    public function catalog_product_save_after(Varien_Event_Observer $observer) {
        try {
            if (!Mage::getStoreConfig(self::$_MODULE_NAMESPACE
                            . '/general/is_active', Mage::app()->getStore())) {
                $this->logToFile('Extension INACTIVE - Quitting...');
                return;
            }

            if (!$this->_isProcessed) {
                $this->_isProcessed = true; // avoid recursion on save()
                $this->processProduct($observer->getProduct());
            } else {
                $this->logToFile('Product already processed '
                        . $observer->getProduct()->getId());
            }
        } catch (Exception $e) {
            $this->logToFile('ERROR: ' . $e->getMessage());
        }
    }

    /**
     * Can be of any product type, e.g. configurable, grouped, simple,
     * @param Mage_Catalog_Model_Product $product Can be of any valid product 
     *        type, e.g. configurable, grouped, simple, ...
     */
    public function processProduct(Mage_Catalog_Model_Product $product) {
        try {
            $this->logToFile('==================================================');
            $this->logToFile('Checking product ' . $product->getId()
                    . ' of type ' . mb_strtoupper($product->getTypeId())
                    . '...');

            /**
             * @var array cleaning options selected in configuration.
             * 
             * Options are taken from system config source view.
             */
            $cleaningOptions = explode(',', Mage::getStoreConfig(self::$_MODULE_NAMESPACE
                            . '/general/cleaning_options', Mage::app()->getStore()));

            $this->logToFile('Cleaning options: ['
                    . implode(',', $cleaningOptions) . ']');

            $this->handleProduct($product, $cleaningOptions);

            $this->logToFile('Done processing product ' . $product->getId() . '!');
        } catch (Exception $e) {
            $this->logToFile('ERROR: ' . $e->getMessage());
        }
    }

}
