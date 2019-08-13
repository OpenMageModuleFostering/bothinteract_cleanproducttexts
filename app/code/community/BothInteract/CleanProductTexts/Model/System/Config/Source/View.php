<?php

/**
 * @author Matthias Kerstner <matthias@both-interact.com>
 * @version 1.0.0
 * @copyright (c) 2015, Both Interact GmbH
 */
class BothInteract_CleanProductTexts_Model_System_Config_Source_View {

    public static $VALUE_CLEANING_TYPE_CONTROL_CHARACTERS = 0;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {
        return array(
            array('value' => self::$VALUE_CLEANING_TYPE_CONTROL_CHARACTERS, 'label' => Mage::helper('adminhtml')->__('Control characters')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray() {
        return array(
            self::$VALUE_CLEANING_TYPE_CONTROL_CHARACTERS => Mage::helper('adminhtml')->__('Control characters')
        );
    }

}
