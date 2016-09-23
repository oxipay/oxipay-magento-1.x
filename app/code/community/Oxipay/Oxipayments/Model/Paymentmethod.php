<?php

class Oxipay_Oxipayments_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract {
    protected $_code  = 'oxipayments';
    protected $_formBlockType = 'oxipayments/form_oxipayments';
    protected $_infoBlockType = 'oxipayments/info_oxipayments';

    public function assignData($data)
    {
        $info = $this->getInfoInstance();
        return $this;
    }

    public function validate()
    {
        parent::validate();
        $info = $this->getInfoInstance();

//        if ($errorMsg)
//        {
//            Mage::throwException($errorMsg);
//        }

        return $this;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('oxipayments/payment/redirect', array('_secure' => false));
    }
}