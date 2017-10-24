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
            ->addFieldToFilter('main_table.state', Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
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

}