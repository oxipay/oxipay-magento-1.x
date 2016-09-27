<?php
class Oxipay_Oxipayments_PaymentController extends Mage_Core_Controller_Front_Action
{
    private $_logFile = 'oxipay.log';
    public function startAction()
    {

        try {
            $gatewayUrl = Oxipay_Oxipayments_Helper_Data::getTransactionIdUrl();
            $httpClient = new Zend_Http_Client($gatewayUrl);
            $payload = $this->getPayload();
            $transactionId = $this->getTransactionId($httpClient, $payload);
            //todo: save transaction id against order
            //$order = Mage::getModel('sales/order');
            //$order->setData('transaction_id', $transactionId);
            //$order->save();

            $checkoutUrl = Oxipay_Oxipayments_Helper_Data::getCheckoutUrl();
            echo "<html><body><form id='form' action='$checkoutUrl' method='post'>";
            foreach($payload as $key => $value) {
                echo "<input type='hidden' id='$key' name='$key' value='$value'/>";
            }
            echo '</form></body>';
            echo '<script>
            var form = document.getElementById("form");
            form.submit();
            </script></html>';
        } catch(Exception $ex) {
            Mage::logException($ex);
            Mage::log('An exception was encountered in oxipayments/paymentcontroller: ' . $ex->getMessage(), null, $this->_logFile);
            Mage::log($ex->getTraceAsString(), null, $this->_logFile);
        }
    }

    public function callbackAction() {
        var_dump($this->getRequest());
        //todo: signature check
        //if bad fail
        //else
        if ($this->getRequest()->get("flag") == "1" && $this->getRequest()->get("orderId"))
        {
            $orderId = $this->getRequest()->get("orderId");
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
           // $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true, 'Payment Success.');
            $order->save();

            Mage::getSingleton('checkout/session')->unsQuoteId();
            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=> false));
        }
        else
        {
            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/error', array('_secure'=> false));
        }
    }

    /**
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
        $apiKey = Mage::getStoreConfig('payment/oxipayments/api_key');
        $signature = Oxipay_Oxipayments_Helper_Data::generateSignature($data, $apiKey);
        $data['x_signature'] = $signature;

        return $data;
    }

    /**
     * @param $httpClient
     */
    private function getTransactionId(Zend_Http_Client $httpClient, $payload)
    {
        $httpClient->setParameterPost($payload);

        $response = $httpClient
            ->setHeaders("Content-Type: application/x-www-form-urlencoded")
            ->request(Zend_Http_Client::POST);

        if ($response === FALSE) {
            Mage::log('No response from the oxipay API. Please check internet connectivity.', null, $this->_logFile);
            //todo: redirect to cart
        }

        return $response->getBody();
    }
}

// transaction
//        $checkout = Mage::getSingleton('core/session', array('name'=>'frontend'));
//        var_dump($checkout);
//
//        $checkout2 = Mage::getSingleton('checkout/session')->getQuote();
//        var_dump($checkout2);
//
//        $checkout3 = Mage::getSingleton('checkout/session')->getOrder();




//