<?php

abstract class ControllerBase extends Mage_Core_Controller_Front_Action {
    const LOG_FILE = 'oxipay.log';

    /**
     * retrieves a transaction id from oxipay. requires a properly constructed payload.
     * @param $payload
     * @return string transaction id
     */
    protected function getTransactionId(array $payload)
    {
        $gatewayUrl = Oxipay_Oxipayments_Helper_Data::getTransactionIdUrl();
        $httpClient = new Zend_Http_Client($gatewayUrl);
        $httpClient->setParameterPost($payload);

        $response = $httpClient
            ->setHeaders("Content-Type: application/x-www-form-urlencoded")
            ->request(Zend_Http_Client::POST);

        if ($response === FALSE) {
            Mage::log('No response from the oxipay API. Please check internet connectivity.', Zend_Log::ERR, self::LOG_FILE);
            $this->_redirectUrl(Mage::getUrl('checkout/cart'));
        }

        return $response->getBody();
    }

    protected function processResponse(Zend_Http_Response $response) {

    }

    /**
     * @throws Mage_Api_Exception
     */
    protected function validateQuote()
    {
        //XSF check
        if(Mage::getStoreConfig('payment/oxipayments/test_mode') == 0 && !$this->_validateFormKey()) {
            Mage::log('XSFT check failed', Zend_Log::WARN, self::LOG_FILE);
            //Mage::throwException("Cross site forgery token check failed.");
            return;
        }
    }

    /**
     * Get current checkout session
     * @return Mage_Core_Model_Abstract
     */
    protected function getCheckoutSession() {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @return false|Mage_Core_Model_Abstract
     */
    protected function getOrder() {
        return Mage::getModel('sales/order');
    }

    /**
     * Injects a self posting form to the page in order to kickoff oxipay checkout process
     * @param $checkoutUrl
     * @param $payload
     */
    protected function postToCheckout(string $checkoutUrl, array $payload)
    {
        echo
        "<html>
            <body>
            <form id='form' action='$checkoutUrl' method='post'>";
            foreach ($payload as $key => $value) {
                echo "<input type='hidden' id='$key' name='$key' value='$value'/>";
            }
            echo
            '</form>
            </body>';
            echo
            '<script>
                var form = document.getElementById("form");
                form.submit();
            </script>
        </html>';
    }

    /**
     * returns an Order object based on magento's internal order id
     * @param $orderId
     * @return Mage_Sales_Model_Order
     */
    protected function getOrderById(string $magentoOrderId)
    {
        return $this->getOrder()->loadByIncrementId($magentoOrderId);
    }

    /**
     * retrieve the merchants oxipay api key
     * @return mixed
     */
    protected function getApiKey()
    {
        return Mage::getStoreConfig('payment/oxipayments/api_key');
    }
}