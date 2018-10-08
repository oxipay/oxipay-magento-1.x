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
    protected $_canUseCheckout = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCapturePartial = true;

    /**
     * Override redirect location of magento's payment method subsystem
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('oxipayments/payment/start', array('_secure' => false));
    }

    public function refund(Varien_Object $payment, $amount)
    {
        $url = 'https://portalssandbox.oxipay.com.au/api/ExternalRefund/processrefund';
        $result = 'unknown';

        $merchant_number = Mage::getStoreConfig('payment/oxipayments/merchant_number');
        $apiKey = Mage::getStoreConfig('payment/oxipayments/api_key');

        if (empty($payment->getData('creditmemo'))) {
            return;
        }
        $refund_amount = $amount;
        $transaction_id = $payment->getData()['creditmemo']->getData('invoice')->getData('transaction_id');
        $refund_details = array(
            "x_merchant_number" => $merchant_number,
            "x_purchase_number" => $transaction_id,
            "x_amount" => $refund_amount,
            "x_reason" => "Refund"
        );

        $refund_signature = Oxipay_Oxipayments_Helper_Crypto::generateSignature($refund_details, $apiKey);
        $refund_details['signature'] = $refund_signature;

        $json = json_encode($refund_details);

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' =>
                    'Content-Type: application/json',
                'content' => $json
            )
        ));

        ini_set("allow_url_fopen", 1);
        $return_message = file_get_contents($url, null, $context);
        $parsed = ($this->parseHeaders($http_response_header));

        if ($parsed['response_code'] == '204') {
            return $this;
        } elseif ($parsed['response_code'] == '401') {
	        Mage::logException(new Exception(sprintf('Oxipay refunding error: Failed Signature Check')));
	        Mage::throwException('Oxipay refunding error: Failed Signature Check when communicating with the Oxipay gateway.');
        } elseif ($parsed['response_code'] == '400') {
	        Mage::logException(new Exception(sprintf('Oxipay refunding error: Gateway returned message')));
	        Mage::throwException('Oxipay refunding failed with error message returned from the Oxipay gateway. Possible reasons: "API Key Not found", "Refund Failed", "Invalid Request"');
        }
}

    function parseHeaders($headers)
    {
        $head = array();
        foreach ($headers as $k => $v) {
            $t = explode(':', $v, 2);
            if (isset($t[1]))
                $head[trim($t[0])] = trim($t[1]);
            else {
                $head[] = $v;
                if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out))
                    $head['response_code'] = intval($out[1]);
            }
        }
        return $head;
    }
}

?>
