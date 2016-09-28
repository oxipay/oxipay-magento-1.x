<?php

/**
 * Class Oxipay_Oxipayments_Model_Paymentmethod
 *
 * overrides basic payment method functionality and visuals
 */
class Oxipay_Oxipayments_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract {
    protected $_code  = 'oxipayments';
    protected $_formBlockType = 'oxipayments/form_oxipayments';
    protected $_infoBlockType = 'oxipayments/info_oxipayments';
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canUseCheckout = false;

    /**
     * @param mixed $data
     * @return $this
     */
//    public function assignData($data)
//    {
//        $info = $this->getInfoInstance();
//        return $this;
//    }
//
//    /**
//     * @return $this
//     */
//    public function validate()
//    {
//        parent::validate();
//        $info = $this->getInfoInstance();
//
//        if ($errorMsg)
//        {
//            Mage::throwException($errorMsg);
//        }
//
//        return $this;
//    }

    /**
     * Override redirect location of magento's payment method subsystem
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('oxipayments/payment/start', array('_secure' => false));
    }
}