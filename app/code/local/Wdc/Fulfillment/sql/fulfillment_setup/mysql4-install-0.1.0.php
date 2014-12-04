<?php
$installer = $this;

$installer->startSetup();

$date = "'" . date('Y-m-d_H-i-s') . "'";

$installer->run("
	
CREATE TABLE IF NOT EXISTS `{$this->getTable('fulfillment/order')}` (
  `fill_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL DEFAULT '0',
  `file_id` int(11) NOT NULL DEFAULT '0',
  `order_type` varchar(50) NOT NULL,
  `response` varchar(5000) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT NULL,
  `status` int(1) DEFAULT '0',
  `attemps` int(2) DEFAULT '0',
  PRIMARY KEY (`fill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `{$this->getTable('fulfillment/shipment')}` (
  `ship_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `shipper_code` varchar(50) NOT NULL,
  `tracking_number` varchar(25) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `shipped_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ship_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `{$this->getTable('fulfillment/file')}` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_header` varchar(100) DEFAULT NULL,
  `order_detail` varchar(100) DEFAULT NULL,
  `file_type` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `status` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`file_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100001 DEFAULT CHARSET=latin1;

");

$installer->endSetup(); 

$installer->setConfigData('fulfillment/ftpgeneral/show_ftp', 0);
$installer->setConfigData('fulfillment/apigeneral/show_xmlapi', 0);