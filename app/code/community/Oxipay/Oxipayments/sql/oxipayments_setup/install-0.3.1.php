<?php
/**
 * Created by PhpStorm.
 * User: jimbur
 * Date: 22/09/2016
 * Time: 3:46 PM
 */
$installer = $this;

$installer->startSetup();

    // add default Oxipay Status "Processing Oxipay" for STATE_PROCESSING state
    $processingState  = Mage_Sales_Model_Order::STATE_PROCESSING;
    $oxipayProcessingStatus = 'oxipay_processing';
    $installer->run("INSERT INTO `{$this->getTable('sales_order_status')}` (`status`, `label`) VALUES ('{$oxipayProcessingStatus}', 'Oxipay Processing');");
    $installer->run("INSERT INTO `{$this->getTable('sales_order_status_state')}` (`status`, `state`, `is_default`) VALUES ('{$oxipayProcessingStatus}', '{$processingState}', '0');");

$installer->endSetup();