<?php

/**
 * Class Oxipay_Oxipayments_Helper_Data
 *
 * Provides helper methods for retrieving data for the oxipay plugin
 */
class Oxipay_Oxipayments_Helper_Data extends Mage_Core_Helper_Abstract
{   
    public static function init()
    {
    }

    /**
     * get the URL of the configured oxipay gateway checkout
     * @return string
     */
    public static function getCheckoutUrl() {
        return Mage::getStoreConfig('payment/oxipayments/gateway_url');
    }

	/**
	 * get the URL of the configured oxipay gateway checkout
	 * @return string
	 */
	public static function getRefundUrl() {
		$checkoutUrl = self::getCheckoutUrl();
		if (strpos($checkoutUrl, ".co.nz") !== false){
			$country_domain = '.co.nz';
		} else {
			$country_domain = '.com.au'; // default value
		}

		if (strpos($checkoutUrl, 'sandbox') === false) {
			$isSandbox = false;
		} else {
			$isSandbox = true; //default value
		}

		if (!$isSandbox){
			return 'https://portals.oxipay'.$country_domain.'/api/ExternalRefund/processrefund';
		} else {
			return 'https://portalssandbox.oxipay'.$country_domain.'/api/ExternalRefund/processrefund';
		}
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
}
Oxipay_Oxipayments_Helper_Data::init();