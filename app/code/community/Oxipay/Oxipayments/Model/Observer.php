<?php

class Oxipay_Oxipayments_Model_Observer
{
    const LOG_FILE = 'oxipay.log';

    const JOB_PROCESSING_LIMIT = 50;

    /**
     * Cron job to cancel Pending Payment Oxipay orders
     *
     * @param Mage_Cron_Model_Schedule $schedule
     */
    public function cancelOxipayPendingOrders(Mage_Cron_Model_Schedule $schedule)
    {
        Mage::log('[oxipay][cron][cancelOxipayPendingOrders]Start', Zend_Log::DEBUG, self::LOG_FILE);

        $orderCollection = Mage::getResourceModel('sales/order_collection');
        $orderCollection->join(
            array('p' => 'sales/order_payment'),
            'main_table.entity_id = p.parent_id',
            array()
        );
        $orderCollection
            ->addFieldToFilter('main_table.state', Oxipay_Oxipayments_Helper_OrderStatus::STATUS_PENDING_PAYMENT)
            ->addFieldToFilter('p.method', array('like' => 'oxipay%'))
            ->addFieldToFilter('main_table.created_at', array('lt' =>  new Zend_Db_Expr("DATE_ADD('".now()."', INTERVAL -'90:00' HOUR_MINUTE)")));

        $orderCollection->setOrder('main_table.updated_at', Varien_Data_Collection::SORT_ORDER_ASC);
        $orderCollection->setPageSize(self::JOB_PROCESSING_LIMIT);

        $orders ="";
        foreach($orderCollection->getItems() as $order)
        {
            $orderModel = Mage::getModel('sales/order');
            $orderModel->load($order['entity_id']);

            if(!$orderModel->canCancel()) {
                continue;
            }

            $orderModel->cancel();

            $history = $orderModel->addStatusHistoryComment('Oxipay payment was not received for this order after 90 minutes');
            $history->save();

            $orderModel->save();
        }

    }

    public function do_refunding($observer = null)
    {
        $url = 'https://portalssandbox.oxipay.com.au/api/ExternalRefund/processrefund';
        $result = 'unknown';

        $merchant_number = Mage::getStoreConfig('payment/oxipayments/merchant_number');
        $apiKey = Mage::getStoreConfig('payment/oxipayments/api_key');

        if (empty($observer->getData('creditmemo'))) {
            return;
        }
        $refund_amount = $observer->getData('creditmemo')['subtotal'];
        $refund_details = array(
            "x_merchant_number" => $merchant_number,
            "x_purchase_number" => "52012332554",
            "x_amount" => $refund_amount,
            "x_reason" => "Test refund"
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

        $return_message = file_get_contents($url, null, $context);
        $parsed = ($this->parseHeaders($http_response_header));

        if ($parsed['response_code'] == '204') {
            $result = 'success';
        } elseif ($parsed['response_code'] == '401') {
            $result = "Failed Signature Check";
        } elseif ($parsed['response_code'] == '400') {
            $result = $return_message;
        }

        return $result;

//        $event = $observer->getEvent();
//        $creditmemo_object = $observer["creditmemo"];
//        $refund_value = $creditmemo_object->getSubtotal();
//        Mage::log('[oxipay][cron][cancelOxipayPendingOrders]Start', Zend_Log::DEBUG, self::LOG_FILE);
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