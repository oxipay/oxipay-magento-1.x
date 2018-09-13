<?php

/**
 * Class Oxipay_Oxipayments_Block_Form_Oxipayments
 * @Description Code behind for the custom Oxipay payment form.
 * @Remarks Invokes the view template in a distant folder structure: ~/app/design/frontend/base/default/template/oxipayments/form.phtml
 *
 */
class Oxipay_Oxipayments_Block_Form_Oxipayments extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $mark = Mage::getConfig()->getBlockClassName('core/template');
        $mark = new $mark;
        $mark->setTemplate('oxipayments/mark.phtml');
        $this->setMethodLabelAfterHtml($mark->toHtml());
        parent::_construct();
        $this->setTemplate('oxipayments/form.phtml');
    }
}