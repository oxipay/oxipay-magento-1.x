<?php

/**
 * Created by PhpStorm.
 * User: jimbur
 * Date: 27/10/2016
 * Time: 5:38 PM
 */
class Oxipay_Oxipayments_Helper_OrderStatus
{
    const STATUS_PENDING_PAYMENT   = 'pending_oxipay';
    const STATUS_PROCESSING = 'oxipay_processed';
    const STATUS_CANCELED = 'cancelled_oxipay';
    const STATUS_DECLINED = 'declined_oxipay';
}