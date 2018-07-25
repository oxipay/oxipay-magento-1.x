<?php

/**
 * Class Ezipay_Ezipayments_Block_Form_Ezipayments
 * @Description Code behind for the custom Ezipay payment form.
 * @Remarks Invokes the view template in a distant folder structure: ~/app/design/frontend/base/default/template/ezipayments/form.phtml
 *
 */
class Ezipay_Ezipayments_Block_Adminhtml_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return (string)Mage::getConfig()->getNode()->modules->Ezipay_Ezipayments->version;
    }
}