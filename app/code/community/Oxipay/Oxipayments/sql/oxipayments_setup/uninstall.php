<?php

$installer = $this;

$installer->startSetup();

$installer->run("DELETE FROM `{$installer->getTable('sales_order_status_state')}` WHERE status='oxipay_processing';");
$installer->run("DELETE FROM `{$installer->getTable('sales_order_status')}` WHERE status='oxipay_processing';");

$installer->endSetup();
?>
