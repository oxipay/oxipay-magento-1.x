<?php

/**
 * Class Ezipay_Ezipayments_Helper_Data
 *
 * Provides helper methods for retrieving data for the Ezipay plugin
 */
class Ezipay_Ezipayments_Helper_Data extends Mage_Core_Helper_Abstract
{   
    public static function init()
    {
    }

    /**
     * get the URL of the configured ezipay gateway checkout
     * @return string
     */
    public static function getCheckoutUrl() {
        return Mage::getStoreConfig('payment/ezipayments/gateway_url');
    }

    /**
     * @return string
     */
    public static function getCompleteUrl() {
        return Mage::getBaseUrl() . 'ezipayments/payment/complete';
    }

    /**
     * @return string
     */
    public static function getCancelledUrl($orderId) {
        return Mage::getBaseUrl() . "ezipayments/payment/cancel?orderId=$orderId";
    }
}
Ezipay_Ezipayments_Helper_Data::init();