<?php

/**
 * Class Oxipay_Oxipayments_Helper_Data
 *
 * Provides helper methods for retrieving data for the oxipay plugin
 */
class Oxipay_Oxipayments_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * get the URL of the configured oxipay gateway checkout
     * @return string
     */
    public static function getCheckoutUrl() {
        return Mage::getStoreConfig('payment/oxipayments/gateway_url');
    }

    /**
     * @return string
     */
    public static function getCompleteUrl() {
        return Mage::getBaseUrl() . 'oxipayments/payment/complete';
    }

    /**
     * @return string
     */
    public static function getCancelledUrl($orderId) {
        return Mage::getBaseUrl() . "oxipayments/payment/cancel?orderId=$orderId";
    }

    /**
     * isDebugMode
     * Get debug setting from backend
     * @return boolean
     */
    public function isDebugMode()
    {
        return Mage::getStoreConfig('payment/oxipayments/debug');
    }
}