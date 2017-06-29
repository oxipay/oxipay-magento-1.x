<?php

/**
 * Class Oxipay_Oxipayments_Block_Form_Oxipayments
 * @Description Code behind for the custom Oxipay payment form.
 * @Remarks Invokes the view template in a distant folder structure: ~/app/design/frontend/base/default/template/oxipayments/form.phtml
 *
 */
class Oxipay_Oxipayments_Block_Adminhtml_Version extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return (string)Mage::getConfig()->getNode()->modules->Oxipay_Oxipayments->version;
    }
}