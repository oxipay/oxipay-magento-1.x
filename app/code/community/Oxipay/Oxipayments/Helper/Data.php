<?php

/**
 * Created by PhpStorm.
 * User: jimbur
 * Date: 22/09/2016
 * Time: 4:14 PM
 */
class Oxipay_Oxipayments_Helper_Data extends Mage_Core_Helper_Abstract
{
    public static function getGatewayUrl()
    {
        $gatewayBaseUrl = Mage::getStoreConfig('payment/oxipayments/gateway_base_url');
        $gatewayRoute = Mage::getStoreConfig('payment/oxipayments/gateway_checkout_url');
        return "$gatewayBaseUrl/$gatewayRoute";
    }

    public static function getTransactionIdUrl() {
        $gatewayBaseUrl = Mage::getStoreConfig('payment/oxipayments/gateway_base_url');
        return "$gatewayBaseUrl/Checkout/Process?platform=Magento";
    }

    public static function getCheckoutUrl() {
        $gatewayBaseUrl = Mage::getStoreConfig('payment/oxipayments/gateway_base_url');
        return "$gatewayBaseUrl/Checkout?platform=magento";
    }

    public static function getCompleteUrl() {
        return Mage::getBaseUrl() . '/checkout/onepage/success';
    }

    public static function getCancelledUrl() {
        return Mage::getBaseUrl() . 'checkout/onepage/failure';
    }

    public static function getCallbackUrl() {
        return Mage::getBaseUrl() . 'oxipayments/payment/callback';
    }

    public static function generateSignature( $query, $api_key ) {
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
}