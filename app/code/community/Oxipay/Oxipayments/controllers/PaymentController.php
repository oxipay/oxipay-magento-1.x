<?php
require_once dirname(__FILE__).'/../Helper/Crypto.php';

class Oxipay_Oxipayments_PaymentController extends Mage_Core_Controller_Front_Action
{
    const LOG_FILE = 'oxipay.log';
    const OXIPAY_DEFAULT_CURRENCY_CODE = 'AUD';
    const OXIPAY_DEFAULT_COUNTRY_CODE = 'AU';

    /**
     * GET: /oxipayments/payment/start
     *
     * Begin processing payment via oxipay
     */
    public function startAction()
    {
        if($this->validateQuote()) {
            try {
                $order = $this->getLastRealOrder();
                $payload = $this->getPayload($order);

                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Oxipay authorisation underway.');
                $order->save();

                $this->postToCheckout(Oxipay_Oxipayments_Helper_Data::getCheckoutUrl(), $payload);
            } catch (Exception $ex) {
                Mage::logException($ex);
                Mage::log('An exception was encountered in oxipayments/paymentcontroller: ' . $ex->getMessage(), Zend_Log::ERR, self::LOG_FILE);
                Mage::log($ex->getTraceAsString(), Zend_Log::ERR, self::LOG_FILE);
                $this->_getCheckoutSession()->addError($this->__('Unable to start Oxipay Checkout.'));
            }
        } else {
            $this->restoreCart($this->getLastRealOrder());
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * GET: /oxipayments/payment/cancel
     * Cancel an order given an order id
     */
    public function cancelAction()
    {
        $orderId = $this->getRequest()->get('orderId');
        $order =  $this->getOrderById($orderId);

        if ($order && $order->getId()) {
            Mage::log(
                'Requested order cancellation by customer. OrderId: ' . $order->getIncrementId(),
                Zend_Log::DEBUG,
                self::LOG_FILE
            );
            $this->cancelOrder($order);
            $this->restoreCart($order);
            $order->save();
        }
        $this->_redirect('checkout/cart');
    }

    /**
     * GET: oxipayments/payment/complete
     *
     * callback - oxipay calls this once the payment process has been completed.
     */
    public function completeAction() {
        $isValid = Oxipay_Oxipayments_Helper_Crypto::isValidSignature($this->getRequest()->getParams(), $this->getApiKey());
        $result = $this->getRequest()->get("x_result");
        $orderId = $this->getRequest()->get("x_reference");
        $transactionId = $this->getRequest()->get("x_gateway_reference");
        $amount = $this->getRequest()->get("x_amount");

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

        //magento likes to have you explicitly hydrate the object, required such that the save on line below doesn't fail
        $unusedPaymentObject = $order->getPayment();

        if ($result == "completed")
        {
            $order->setTotalPaid($amount);
            $order->addStatusHistoryComment($this->__("Oxipay authorisation success. Transaction #$transactionId"), 
                Mage_Sales_Model_Order::STATE_COMPLETE)->setIsCustomerNotified(true);
            $order->sendNewOrderEmail();
            $order->save();

            Mage::getSingleton('checkout/session')->unsQuoteId();
            $this->_redirect('checkout/onepage/success', array('_secure'=> false));
        }
        else
        {
            $order
                ->cancel()
                ->addStatusHistoryComment($this->__("Order #: $order->getId() was rejected by oxipay. Transaction #$transactionId"));

            $this->restoreCart($order);
            $order->save();

            Mage::getSingleton('checkout/session')->unsQuoteId();
            $this->_redirect('checkout/onepage/failure', array('_secure'=> false));
        }

    }

    /**
     * Constructs a request payload to send to oxipay
     * @return array
     */
    private function getPayload($order) {
        if($order == null)
        {
            Mage::log('Unable to get order from last lodged order id. Possibly related to a failed database call.', Zend_Log::ALERT, self::LOG_FILE);
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
        }

        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        $orderId = $order->getRealOrderId();
        $data = array(
            'x_currency' => str_replace(PHP_EOL, ' ', $order->getOrderCurrencyCode()),
            'x_url_callback' => str_replace(PHP_EOL, ' ', Oxipay_Oxipayments_Helper_Data::getCompleteUrl()),
            'x_url_complete' => str_replace(PHP_EOL, ' ', Oxipay_Oxipayments_Helper_Data::getCompleteUrl()),
            'x_url_cancel' => str_replace(PHP_EOL, ' ', Oxipay_Oxipayments_Helper_Data::getCancelledUrl($orderId)),
            'x_shop_name' => str_replace(PHP_EOL, ' ', Mage::app()->getStore()->getCode()),
            'x_account_id' => str_replace(PHP_EOL, ' ', Mage::getStoreConfig('payment/oxipayments/merchant_number')),
            'x_reference' => str_replace(PHP_EOL, ' ', $orderId),
            'x_invoice' => str_replace(PHP_EOL, ' ', $orderId),
            'x_amount' => str_replace(PHP_EOL, ' ', $order->getTotalDue()),
            'x_customer_first_name' => str_replace(PHP_EOL, ' ', $order->getCustomerFirstname()),
            'x_customer_last_name' => str_replace(PHP_EOL, ' ', $order->getCustomerLastname()),
            'x_customer_email' => str_replace(PHP_EOL, ' ', $order->getData('customer_email')),
            'x_customer_phone' => str_replace(PHP_EOL, ' ', $billingAddress->getData('telephone')),
            'x_customer_billing_address1' => str_replace(PHP_EOL, ' ', $billingAddress->getData('street')),
            'x_customer_billing_address2' => '',
            'x_customer_billing_city' => str_replace(PHP_EOL, ' ', $billingAddress->getData('city')),
            'x_customer_billing_state' => str_replace(PHP_EOL, ' ', $billingAddress->getData('region')),
            'x_customer_billing_zip' => str_replace(PHP_EOL, ' ', $billingAddress->getData('postcode')),
            'x_customer_shipping_address1' => str_replace(PHP_EOL, ' ', $shippingAddress->getData('street')),
            'x_customer_shipping_address2' => '',
            'x_customer_shipping_city' => str_replace(PHP_EOL, ' ', $shippingAddress->getData('city')),
            'x_customer_shipping_state' => str_replace(PHP_EOL, ' ', $shippingAddress->getData('region')),
            'x_customer_shipping_zip' => str_replace(PHP_EOL, ' ', $shippingAddress->getData('postcode')),
            'x_test' => 'false'
        );
        $apiKey = $this->getApiKey();
        $signature = Oxipay_Oxipayments_Helper_Crypto::generateSignature($data, $apiKey);
        $data['x_signature'] = $signature;

        return $data;
    }

    /**
     * checks the quote for validity
     * @throws Mage_Api_Exception
     */
    private function validateQuote()
    {
        $order = $this->getLastRealOrder();
        if($order->getTotalDue() < 20) {
            Mage::getSingleton('checkout/session')->addError("Oxipay doesn't support purchases less than $20.");
            return false;
        }

        if($order->getBillingAddress()->getCountry() != self::OXIPAY_DEFAULT_COUNTRY_CODE || $order->getOrderCurrencyCode() != self::OXIPAY_DEFAULT_CURRENCY_CODE) {
            Mage::getSingleton('checkout/session')->addError("Oxipay doesn't support purchases from outside Australia.");
            return false;
        }

        if($order->getShippingAddress()->getCountry() != self::OXIPAY_DEFAULT_COUNTRY_CODE) {
            Mage::getSingleton('checkout/session')->addError("Oxipay doesn't support purchases shipped outside Australia.");
            return false;
        }

        return true;
    }

    /**
     * Get current checkout session
     * @return Mage_Core_Model_Abstract
     */
    private function getCheckoutSession() {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Injects a self posting form to the page in order to kickoff oxipay checkout process
     * @param $checkoutUrl
     * @param $payload
     */
    private function postToCheckout($checkoutUrl, $payload)
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
    private function getOrderById($orderId)
    {
        return Mage::getModel('sales/order')->loadByIncrementId($orderId);
    }

    /**
     * retrieve the merchants oxipay api key
     * @return mixed
     */
    private function getApiKey()
    {
        return Mage::getStoreConfig('payment/oxipayments/api_key');
    }

    /**
     * retrieve the last order created by this session
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

    /**
     * @param Mage_Sales_Model_Order $order
     * @return $this
     * @throws Exception
     */
    private function cancelOrder(Mage_Sales_Model_Order $order)
    {
        if (!$order->isCanceled()) {
            $order
                ->cancel()
                ->addStatusHistoryComment($this->__("Oxipay: $order->getId() was cancelled by the customer."));
        }
        return $this;
    }

    /**
     * Loads the cart with items from the order
     * @param Mage_Sales_Model_Order $order
     * @return $this
     */
    private function restoreCart(Mage_Sales_Model_Order $order)
    {
        // return all products to shopping cart
        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        if ($quote->getId()) {
            $quote->setIsActive(1)->setReservedOrderId(null)->save();
            $this->getCheckoutSession()->replaceQuote($quote);
        }
        return $this;
    }

}
