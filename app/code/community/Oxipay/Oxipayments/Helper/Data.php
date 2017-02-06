<?php

/**
 * Class Oxipay_Oxipayments_Helper_Data
 *
 * Provides helper methods for retrieving data for the oxipay plugin
 */
class Oxipay_Oxipayments_Helper_Data extends Mage_Core_Helper_Abstract
{
    public static $gatewayBaseUrl;
    public static $gatewayRoute;

    public static function init()
    {
        self::$gatewayBaseUrl = Mage::getStoreConfig('payment/oxipayments/gateway_base_url');
        self::$gatewayRoute = Mage::getStoreConfig('payment/oxipayments/gateway_checkout_url');
    }

    /**
     * get the URL of the configured oxipay gateway checkout
     * @return string
     */
    public static function getCheckoutUrl() {
        $scheme = parse_url(self::$gatewayBaseUrl, PHP_URL_SCHEME);
        $host = parse_url(self::$gatewayBaseUrl, PHP_URL_HOST);
        $port = parse_url(self::$gatewayBaseUrl, PHP_URL_PORT);

        return "$scheme://$host:$port/Checkout?platform=default";
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
    public static function getCancelledUrl(number $orderId) {
        return Mage::getBaseUrl() . "oxipayments/payment/cancel?orderId=$orderId";
    }
}
Oxipay_Oxipayments_Helper_Data::init();