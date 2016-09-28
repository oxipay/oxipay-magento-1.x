<?php

class Oxipay_Oxipayments_PaymentController extends ControllerBase
{
    /**
     * GET: /oxipayments/payment/start
     *
     * Begin processing payment via oxipay
     */
    public function startAction()
    {
        $this->validateQuote();

        try {

            $payload = $this->getPayload();
            $transactionId = $this->getTransactionId($payload);

            $order = $this->getOrder();
            $payment = $order->getPayment();
            $payment->setTransactionId($transactionId);
            $order->setTransactionId($transactionId);
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Oxipay authorization processing.');
            $this->postToCheckout(Helper::getCheckoutUrl(), $payload);

        } catch(Exception $ex) {
            Mage::logException($ex);
            Mage::log('An exception was encountered in oxipayments/paymentcontroller: ' . $ex->getMessage(), Zend_Log::ERR, self::LOG_FILE);
            Mage::log($ex->getTraceAsString(), Zend_Log::ERR, self::LOG_FILE);
        }
    }

    /**
     * GET: oxipayments/payment/approve
     *
     * async callback - oxipay calls this once payment has been approved.
     */
    public function approveAction() {
        $isValid = Helper::isValidSignature($this->getRequest()->getParams(), $this->_getApiKey());

        if(!$isValid) {
            Mage::log('Possible site forgery detected: invalid response signature.', Zend_Log::ALERT, self::LOG_FILE);
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            return;
        }

        if ($this->getRequest()->get("flag") == "1" && $this->getRequest()->get("orderId"))
        {
            $orderId = $this->getRequest()->get("orderId");
            $order = $this->_getOrderById($orderId);
            $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true, 'Oxipay authorization Success.');
            $order->save();

            Mage::getSingleton('checkout/session')->unsQuoteId();
            $this->_redirect('checkout/onepage/success', array('_secure'=> false));
        }
        else
        {
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
        }
    }

    /**
     * GET: oxipayments/payment/cancel
     * async callback - oxipay calls this once a payment has been cancelled.
     */
    public function cancelAction() {

    }

    /**
     * Constructs a request payload to send to oxipay
     * @return array
     */
    private function getPayload() {
        $order = Mage::getModel("sales/order")->getCollection()->getLastItem();

        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        $data = array(
            'x_currency' => 'AU',
            'x_url_complete' => Oxipay_Oxipayments_Helper_Data::getCompleteUrl(),
            'x_url_callback' => Oxipay_Oxipayments_Helper_Data::getCallbackUrl(),
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
        $apiKey = $this->_getApiKey();
        $signature = Oxipay_Oxipayments_Helper_Data::generateSignature($data, $apiKey);
        $data['x_signature'] = $signature;

        return $data;
    }


}