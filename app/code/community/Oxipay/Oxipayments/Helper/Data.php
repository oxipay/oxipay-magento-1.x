<?php

/**
 * Class Oxipay_Oxipayments_Helper_Data
 *
 * Provides helper methods for retrieving data for the oxipay plugin
 */
class Oxipay_Oxipayments_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * get the URL of the configured oxipay gateway
     * @return string
     */
    public static function getGatewayUrl()
    {
        $gatewayBaseUrl = Mage::getStoreConfig('payment/oxipayments/gateway_base_url');
        $gatewayRoute = Mage::getStoreConfig('payment/oxipayments/gateway_checkout_url');
        return "$gatewayBaseUrl/$gatewayRoute";
    }

    /**
     * get the URL of the configured oxipay gateways transaction id processing action
     * @return string
     */
    public static function getTransactionIdUrl() {
        $gatewayBaseUrl = Mage::getStoreConfig('payment/oxipayments/gateway_base_url');
        return "$gatewayBaseUrl/Checkout/Process?platform=Magento";
    }

    /**
     * get the URL of the configured oxipay gateway checkout
     * @return string
     */
    public static function getCheckoutUrl() {
        $gatewayBaseUrl = Mage::getStoreConfig('payment/oxipayments/gateway_base_url');
        return "$gatewayBaseUrl/Checkout?platform=magento";
    }

    /**
     * @return string
     */
    public static function getCompleteUrl() {
        return Mage::getBaseUrl() . '/checkout/onepage/success';
    }

    /**
     * @return string
     */
    public static function getCancelledUrl() {
        return Mage::getBaseUrl() . 'checkout/onepage/failure';
    }

    /**
     * @return string
     */
    public static function getCallbackUrl() {
        return Mage::getBaseUrl() . 'oxipayments/payment/callback';
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
        $query['x_signature'] = null;

        $expectedSignature = self::generateSignature($query, $api_key);
        return $actualSignature == $expectedSignature;
    }
}