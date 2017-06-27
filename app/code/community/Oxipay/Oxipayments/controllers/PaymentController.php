<?php
require_once dirname(__FILE__).'/../Helper/Crypto.php';

class Oxipay_Oxipayments_PaymentController extends Mage_Core_Controller_Front_Action
{
    const LOG_FILE = 'oxipay.log';
    const OXIPAY_AU_CURRENCY_CODE = 'AUD';
    const OXIPAY_AU_COUNTRY_CODE = 'AU';
    const OXIPAY_NZ_CURRENCY_CODE = 'NZD';
    const OXIPAY_NZ_COUNTRY_CODE = 'NZ';

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

                //Mage_Sales_Model_Order::setState($state, $status=false, $comment='', $isCustomerNotified=false)
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, 'Oxipay authorisation underway.');
                $order->save();

                $this->postToCheckout(Oxipay_Oxipayments_Helper_Data::getCheckoutUrl(), $payload);
            } catch (Exception $ex) {
                Mage::logException($ex);
                Mage::log('An exception was encountered in oxipayments/paymentcontroller: ' . $ex->getMessage(), Zend_Log::ERR, self::LOG_FILE);
                Mage::log($ex->getTraceAsString(), Zend_Log::ERR, self::LOG_FILE);
                $this->getCheckoutSession()->addError($this->__('Unable to start Oxipay Checkout.'));
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

        // ensure that we have a Mage_Sales_Model_Order
        if (get_class($order) !== 'Mage_Sales_Model_Order') {
            Mage::log("The instance of order returned is an unexpected type.", Zend_Log::ERR, self::LOG_FILE);
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            return;
        }

        if($result == "completed" && $order->getState() === Mage_Sales_Model_Order::STATE_PROCESSING) {
            $this->_redirect('checkout/onepage/success', array('_secure'=> false));
            return;
        }

        if($result == "failed" && $order->getState() === Mage_Sales_Model_Order::STATE_CANCELED) {
            $this->_redirect('checkout/onepage/failure', array('_secure'=> false));
            return;
        }

        //magento likes to have you explicitly hydrate the object, required such that the save on line below doesn't fail
        $unusedPaymentObject = $order->getPayment();

        if ($result == "completed")
        {
            $orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
            $orderStatus = Mage::getStoreConfig('payment/oxipayments/oxipay_approved_order_status');
            if (!$this->statusExists($orderStatus)) {
                $orderStatus = $order->getConfig()->getStateDefaultStatus($orderState);
            }

            $emailCustomer = Mage::getStoreConfig('payment/oxipayments/email_customer');
            if ($emailCustomer) {
                $order->sendNewOrderEmail();
            }
            $order->setState($orderState, $orderStatus ? $orderStatus : true, $this->__("Oxipay authorisation success. Transaction #$transactionId"), $emailCustomer);

            $order->save();

            $invoiceAutomatically = Mage::getStoreConfig('payment/oxipayments/automatic_invoice');
            if ($invoiceAutomatically) {
                $this->invoiceOrder($order);
            }

            Mage::getSingleton('checkout/session')->unsQuoteId();
            $this->_redirect('checkout/onepage/success', array('_secure'=> false));
        }
        else
        {
            $order
                ->cancel()
                ->addStatusHistoryComment($this->__("Order #".($order->getId())." was rejected by oxipay. Transaction #$transactionId."));

            $this->restoreCart($order);
            $order->save();

            Mage::getSingleton('checkout/session')->unsQuoteId();
            $this->_redirect('checkout/onepage/failure', array('_secure'=> false));
        }

    }

    private function statusExists($orderStatus) {
        try {
            $orderStatusModel = Mage::getModel('sales/order_status');
            if ($orderStatusModel) {
                $statusesResCol = $orderStatusModel->getResourceCollection();
                if ($statusesResCol) {
                    $statuses = $statusesResCol->getData();
                    foreach ($statuses as $status) {
                        if ($orderStatus === $status["status"]) return true;
                    }
                }
            }
        } catch(Exception $e) {
            Mage::log("Exception searching statuses: ".($e->getMessage()), Zend_Log::ERR, self::LOG_FILE);
        }
        return false;
    }

    private function invoiceOrder(Mage_Sales_Model_Order $order) {

        if(!$order->canInvoice()){
            Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
        }

        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

        if (!$invoice->getTotalQty()) {
            Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
        }

        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        $invoice->register();
        $transactionSave = Mage::getModel('core/resource_transaction')
        ->addObject($invoice)
        ->addObject($invoice->getOrder());

        $transactionSave->save();
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

        $billingAddressParts = explode(PHP_EOL, $billingAddress->getData('street'));
        $billingAddress0 = $billingAddressParts[0];
        $billingAddress1 = (count($billingAddressParts)>1)? $billingAddressParts[1]:'';

        $shippingAddressParts = explode(PHP_EOL, $shippingAddress->getData('street'));
        $shippingAddress0 = $shippingAddressParts[0];
        $shippingAddress1 = (count($shippingAddressParts)>1)? $shippingAddressParts[1]:'';

        $orderId = (int)$order->getRealOrderId();
        $canceledURL  = Oxipay_Oxipayments_Helper_Data::getCancelledUrl($orderId);
        $data = array(
            'x_currency'            => str_replace(PHP_EOL, ' ', $order->getOrderCurrencyCode()),
            'x_url_callback'        => str_replace(PHP_EOL, ' ', Oxipay_Oxipayments_Helper_Data::getCompleteUrl()),
            'x_url_complete'        => str_replace(PHP_EOL, ' ', Oxipay_Oxipayments_Helper_Data::getCompleteUrl()),
            'x_url_cancel'          => str_replace(PHP_EOL, ' ', Oxipay_Oxipayments_Helper_Data::getCancelledUrl($orderId)),
            'x_shop_name'           => str_replace(PHP_EOL, ' ', Mage::app()->getStore()->getCode()),
            'x_account_id'          => str_replace(PHP_EOL, ' ', Mage::getStoreConfig('payment/oxipayments/merchant_number')),
            'x_reference'           => str_replace(PHP_EOL, ' ', $orderId),
            'x_invoice'             => str_replace(PHP_EOL, ' ', $orderId),
            'x_amount'              => str_replace(PHP_EOL, ' ', $order->getTotalDue()),
            'x_customer_first_name' => str_replace(PHP_EOL, ' ', $order->getCustomerFirstname()),
            'x_customer_last_name'  => str_replace(PHP_EOL, ' ', $order->getCustomerLastname()),
            'x_customer_email'      => str_replace(PHP_EOL, ' ', $order->getData('customer_email')),
            'x_customer_phone'      => str_replace(PHP_EOL, ' ', $billingAddress->getData('telephone')),
            'x_customer_billing_address1'  => $billingAddress0,
            'x_customer_billing_address2'  => $billingAddress1,
            'x_customer_billing_city'      => str_replace(PHP_EOL, ' ', $billingAddress->getData('city')),
            'x_customer_billing_state'     => str_replace(PHP_EOL, ' ', $billingAddress->getData('region')),
            'x_customer_billing_zip'       => str_replace(PHP_EOL, ' ', $billingAddress->getData('postcode')),
            'x_customer_shipping_address1' => $shippingAddress0,
            'x_customer_shipping_address2' => $shippingAddress1,
            'x_customer_shipping_city'     => str_replace(PHP_EOL, ' ', $shippingAddress->getData('city')),
            'x_customer_shipping_state'    => str_replace(PHP_EOL, ' ', $shippingAddress->getData('region')),
            'x_customer_shipping_zip'      => str_replace(PHP_EOL, ' ', $shippingAddress->getData('postcode')),
            'x_test'                       => 'false'
        );
        $apiKey    = $this->getApiKey();
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
        $specificCurrency = null;

        if ($this->getSpecificCountry() == self::OXIPAY_AU_COUNTRY_CODE) {
            $specificCurrency = self::OXIPAY_AU_CURRENCY_CODE;
        }
        else if ($this->getSpecificCountry() == self::OXIPAY_NZ_COUNTRY_CODE) {
            $specificCurrency = self::OXIPAY_NZ_CURRENCY_CODE;
        }

        $order = $this->getLastRealOrder();

        if($order->getTotalDue() < 20) {
            Mage::getSingleton('checkout/session')->addError("Oxipay doesn't support purchases less than $20.");
            return false;
        }

        if($order->getBillingAddress()->getCountry() != $this->getSpecificCountry() || $order->getOrderCurrencyCode() != $specificCurrency ) {
            Mage::getSingleton('checkout/session')->addError("Orders from this country are not supported by Oxipay. Please select a different payment option.");
            return false;
        }

        if($order->getShippingAddress()->getCountry() != $this->getSpecificCountry()) {
            Mage::getSingleton('checkout/session')->addError("Orders shipped to this country are not supported by Oxipay. Please select a different payment option.");
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
            echo "<input type='hidden' id='$key' name='$key' value='".htmlspecialchars($value, ENT_QUOTES)."'/>";
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
    * Get specific country
    *
    * @return string
    */
    public function getSpecificCountry()
    {
      return Mage::getStoreConfig('payment/oxipayments/specificcountry');
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
     * Method is called when an order is cancelled by a customer. As an Oxipay reference is only passed back to
     * Magento upon a success or decline outcome, the method will return a message with a Magento reference only.
     *
     * @param Mage_Sales_Model_Order $order
     * @return $this
     * @throws Exception
     */
    private function cancelOrder(Mage_Sales_Model_Order $order)
    {
        if (!$order->isCanceled()) {
            $order
                ->cancel()
                ->addStatusHistoryComment($this->__("Order #".($order->getId())." was canceled by customer."));
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
