<?php
/**
 * Created by PhpStorm.
 * User: jimbur
 * Date: 22/09/2016
 * Time: 3:46 PM
 */
$installer = $this;

$installer->startSetup();

    // add default Oxipay Status "Oxipay Processed" for STATE_PROCESSING state
    $processingState  = Mage_Sales_Model_Order::STATE_PROCESSING;
    $oxipayProcessingStatus = 'oxipay_processed';
    $installer->run("INSERT INTO `{$this->getTable('sales_order_status')}` (`status`, `label`) VALUES ('{$oxipayProcessingStatus}', 'Oxipay Processed');");
    $installer->run("INSERT INTO `{$this->getTable('sales_order_status_state')}` (`status`, `state`, `is_default`) VALUES ('{$oxipayProcessingStatus}', '{$processingState}', '0');");

$installer->endSetup();