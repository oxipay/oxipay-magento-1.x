<?php

/**
 * Class Ezipay_Ezipayments_Model_Paymentmethod
 *
 * overrides basic payment method functionality and visuals
 */
class Ezipay_Ezipayments_Model_Paymentmethod extends Mage_Payment_Model_Method_Abstract {
    protected $_code  = 'ezipayments';
    protected $_formBlockType = 'ezipayments/form_ezipayments';
    protected $_infoBlockType = 'ezipayments/info_ezipayments';
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canUseCheckout = true;


    /**
     * Override redirect location of magento's payment method subsystem
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('ezipayments/payment/start', array('_secure' => false));
    }
}

?>
