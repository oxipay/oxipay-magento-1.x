<?php

class Oxipay_Oxipayments_PaymentController extends Mage_Core_Controller_Front_Action
{

    const LOG_FILE = 'oxipay.log';
    /**
     * GET: /oxipayments/payment/start
     *
     * Begin processing payment via oxipay
     */
    public function startAction()
    {
        $this->validateQuote();

        try {
            $order = $this->getLastRealOrder();
            $payload = $this->getPayload($order);

            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, 'Oxipay authorisation underway.');
            $order->save();

            $this->postToCheckout(Oxipay_Oxipayments_Helper_Data::getCheckoutUrl(), $payload);
        } catch(Exception $ex) {
            Mage::logException($ex);
            Mage::log('An exception was encountered in oxipayments/paymentcontroller: ' . $ex->getMessage(), Zend_Log::ERR, self::LOG_FILE);
            Mage::log($ex->getTraceAsString(), Zend_Log::ERR, self::LOG_FILE);
            $this->_getCheckoutSession()->addError($this->__('Unable to start Oxipay Checkout.'));
        }
    }

    /**
     * GET: oxipayments/payment/complete
     *
     * callback - oxipay calls this once the payment process has been completed.
     */
    public function completeAction() {
        $isValid = Oxipay_Oxipayments_Helper_Data::isValidSignature($this->getRequest()->getParams(), $this->getApiKey());
        $result = $this->getRequest()->get("x_result");
        $orderId = $this->getRequest()->get("x_reference");
        if(!$isValid) {
            Mage::log('Possible site forgery detected: invalid response signature.', Zend_Log::ALERT, self::LOG_FILE);
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            return;
        }



        if(!$orderId) {
            Mage::log("Oxipay returned a null order id. This may indicate an issue with the Oxipay payment gateway.", Zend_Log::ERR, self::LOG_FILE);
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            return;
        }

        $order = $this->getOrderById($orderId);
        if(!$order) {
            Mage::log("Oxipay returned an id for an order that could not be retrieved: $orderId", Zend_Log::ERR, self::LOG_FILE);
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            return;
        }

        //magento likes to have you explicitly hydrate the object, required such that the save on line 66 doesn't fail
        $unusedPaymentObject = $order->getPayment();

        if ($result == "completed")
        {
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Oxipay authorisation success.');
            $order->save();

            Mage::getSingleton('checkout/session')->unsQuoteId();
            $this->_redirect('checkout/onepage/success', array('_secure'=> false));
        }
        else
        {
            $order
                ->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Oxipay authorisation failed.')
                ->save();

            Mage::getSingleton('checkout/session')->unsQuoteId();
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
        }
    }

    /**
     * Constructs a request payload to send to oxipay
     * @return array
     */
    private function getPayload($order) {;
        if($order == null)
        {
            Mage::log('Unable to get order from last lodged order id. Possibly related to a failed database call.', Zend_Log::ALERT, self::LOG_FILE);
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
        }

        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        $data = array(
            'x_currency' => 'AU',
            'x_url_complete' => Oxipay_Oxipayments_Helper_Data::getCompleteUrl(),
            'x_url_cancel' => Oxipay_Oxipayments_Helper_Data::getCancelledUrl(),
            'x_shop_name' => Mage::app()->getStore()->getCode(),
            'x_account_id' => Mage::getStoreConfig('payment/oxipayments/merchant_number'),
            'x_reference' => $order->getRealOrderId(),
            'x_invoice' => $order->getRealOrderId(),
            'x_amount' => $order->getTotalDue(),
            'x_customer_first_name' => $order->getCustomerFirstname(),
            'x_customer_last_name' => $order->getCustomerLastname(),
            'x_customer_email' => $order->getData('customer_email'),
            'x_customer_phone' => $billingAddress->getData('telephone'),
            'x_customer_billing_address1' => $billingAddress->getData('street'),
            'x_customer_billing_address2' => '',
            'x_customer_billing_city' => $billingAddress->getData('city'),
            'x_customer_billing_state' => $billingAddress->getData('region'),
            'x_customer_billing_zip' => $billingAddress->getData('postcode'),
            'x_customer_shipping_address1' => $shippingAddress->getData('street'),
            'x_customer_shipping_address2' => '',
            'x_customer_shipping_city' => $shippingAddress->getData('city'),
            'x_customer_shipping_state' => $shippingAddress->getData('region'),
            'x_customer_shipping_zip' => $shippingAddress->getData('postcode'),
            'x_test' => Mage::getStoreConfig('payment/oxipayments/test_mode')
        );
        $apiKey = $this->getApiKey();
        $signature = Oxipay_Oxipayments_Helper_Data::generateSignature($data, $apiKey);
        $data['x_signature'] = $signature;

        return $data;
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
     * Injects a self posting form to the page in order to kickoff oxipay checkout process
     * @param $checkoutUrl
     * @param $payload
     */
    protected function postToCheckout($checkoutUrl, $payload)
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
    protected function getOrderById($orderId)
    {
        return Mage::getModel('sales/order')->loadByIncrementId($orderId);
    }

    /**
     * retrieve the merchants oxipay api key
     * @return mixed
     */
    protected function getApiKey()
    {
        return Mage::getStoreConfig('payment/oxipayments/api_key');
    }

    /**
     * @return null
     */
    private function getLastRealOrder()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        $order =
            ($orderId)
                ? $this->getOrderById($orderId)
                : null;
        return $order;
    }
}