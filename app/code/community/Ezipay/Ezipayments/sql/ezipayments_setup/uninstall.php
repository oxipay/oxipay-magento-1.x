<?php

$installer = $this;

$installer->startSetup();

$installer->run("DELETE FROM `{$installer->getTable('sales_order_status_state')}` WHERE status='ezipay_processing';");
$installer->run("DELETE FROM `{$installer->getTable('sales_order_status')}` WHERE status='ezipay_processing';");

$installer->endSetup();
?>
