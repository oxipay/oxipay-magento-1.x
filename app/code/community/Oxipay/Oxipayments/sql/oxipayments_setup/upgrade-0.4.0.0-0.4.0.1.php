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
$installer->run("INSERT INTO `{$this->getTable('sales_order_status')}` (`status`, `label`) VALUES ('pending_oxipay', 'Pending Oxipay');");

// update the existing status
$installer->run("UPDATE `{$this->getTable('sales_order_status')}` set `label`= 'Oxipay Processed' where `status`='oxipay_processed'");


// @todo Oxipay Cancelled
$installer->run("INSERT INTO `{$this->getTable('sales_order_status')}` (`status`, `label`) VALUES ('cancelled_oxipay', 'Cancelled Oxipay');");

// Declined Oxipay
$installer->run("INSERT INTO `{$this->getTable('sales_order_status')}` (`status`, `label`) VALUES ('declined_oxipay', 'Declined Oxipay');");


$installer->endSetup();