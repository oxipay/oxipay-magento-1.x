<?php
/**
 * Class Oxipay_Oxipayments_Info_Form_Oxipayments
 * @Description Code behind for the custom Oxipay payment info block.
 * @Remarks Invokes the view template in a distant folder structure: ~/app/design/frontend/base/default/template/oxipayments/info.phtml
 *
 */
class Oxipay_Oxipayments_Block_Info_Oxipayments extends Mage_Payment_Block_Info
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('oxipayments/info.phtml');
    }
}