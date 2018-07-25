<?php

/**
 * Created by PhpStorm.
 * User: jimbur
 * Date: 27/10/2016
 * Time: 5:38 PM
 */
class Ezipay_Ezipayments_Helper_OrderStatus
{
    const STATUS_PENDING_PAYMENT   = 'pending_ezipay';
    const STATUS_PROCESSING = 'ezipay_processed';
    const STATUS_CANCELED = 'cancelled_ezipay';
    const STATUS_DECLINED = 'declined_ezipay';
}