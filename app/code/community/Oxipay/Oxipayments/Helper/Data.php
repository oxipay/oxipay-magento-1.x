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
     * get the URL of the configured oxipay gateways transaction id processing action
     * @return string
     */
    public static function getTransactionIdUrl() {

        return self::$gatewayBaseUrl . self::$gatewayRoute . '/Process?platform=Magento';
    }

    /**
     * get the URL of the configured oxipay gateway checkout
     * @return string
     */
    public static function getCheckoutUrl() {
        return self::$gatewayBaseUrl . self::$gatewayRoute . '?platform=magento';
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
    public static function getCancelledUrl() {
        return Mage::getBaseUrl() . 'oxipayments/payment/cancelled';
    }

    /**
     * generates a hmac based on an associative array and an api key
     * @param $query array
     * @param $api_key string
     * @return string
     */
    public static function generateSignature($query, $api_key ) {
        //step 1: order by key_name ascending
        $clear_text = '';
        ksort($query);
        foreach ($query as $key => $value) {
            $clear_text .= $key . $value;
        }
        $hash = base64_encode(hash_hmac( "sha256", $clear_text, $api_key . '&', true ));
        $hash = str_replace('+', '', $hash);
        return $hash;
    }


    /**
     * validates and associative array that contains a hmac signature against an api key
     * @param $query array
     * @param $api_key string
     * @return bool
     */
    public static function isValidSignature($query, $api_key) {
        $actualSignature = $query['x_signature'];
        unset($query['x_signature']);

        $expectedSignature = self::generateSignature($query, $api_key);
        return $actualSignature == $expectedSignature;
    }
}
Oxipay_Oxipayments_Helper_Data::init();