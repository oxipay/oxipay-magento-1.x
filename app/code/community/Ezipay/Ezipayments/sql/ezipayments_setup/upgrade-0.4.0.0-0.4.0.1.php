<?php
/**
 * Created by PhpStorm.
 * User: jimbur
 * Date: 22/09/2016
 * Time: 3:46 PM
 */
$installer = $this;

$installer->startSetup();

    // add default Ezipay Status "Ezipay Processed" for STATE_PROCESSING state
$installer->run("INSERT INTO `{$this->getTable('sales_order_status')}` (`status`, `label`) VALUES ('pending_ezipay', 'Pending Ezipay');");

// update the existing status
$installer->run("UPDATE `{$this->getTable('sales_order_status')}` set `label`= 'Ezipay Processed' where `status`='ezipay_processed'");


// @todo Ezipay Cancelled
$installer->run("INSERT INTO `{$this->getTable('sales_order_status')}` (`status`, `label`) VALUES ('cancelled_ezipay', 'Cancelled Ezipay');");

// Declined Ezipay
$installer->run("INSERT INTO `{$this->getTable('sales_order_status')}` (`status`, `label`) VALUES ('declined_ezipay', 'Declined Ezipay');");


$installer->endSetup();