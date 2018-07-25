<?php

/**
 * Class Ezipay_Ezipayments_Block_Form_Ezipayments
 * @Description Code behind for the custom Ezipay payment form.
 * @Remarks Invokes the view template in a distant folder structure: ~/app/design/frontend/base/default/template/ezipayments/form.phtml
 *
 */
class Ezipay_Ezipayments_Block_Form_Ezipayments extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ezipayments/form.phtml');
    }
}