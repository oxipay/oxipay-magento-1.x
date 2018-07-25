<?php
/**
 * Class Ezipay_Ezipayments_Info_Form_Ezipayments
 * @Description Code behind for the custom Ezipay payment info block.
 * @Remarks Invokes the view template in a distant folder structure: ~/app/design/frontend/base/default/template/ezipayments/info.phtml
 *
 */
class Ezipay_Ezipayments_Block_Info_Ezipayments extends Mage_Payment_Block_Info
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ezipayments/info.phtml');
    }
}