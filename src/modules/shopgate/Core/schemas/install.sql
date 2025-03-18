-- This file is automatically executed via the ShopgateInstallHelper.
-- Please DON'T use multiline comments in here.
-- Also, make sure that this file contains no semicolons other than the statement delimiters.

ALTER TABLE `oxarticles` ADD COLUMN `marm_shopgate_marketplace` TINYINT UNSIGNED NOT NULL DEFAULT '1';
ALTER TABLE `oxarticles` ADD COLUMN `marm_shopgate_export` TINYINT UNSIGNED NOT NULL DEFAULT '1';

ALTER TABLE `oxpayments` ADD COLUMN `shopgate_payment_method` VARCHAR( 100 ) NULL;
INSERT IGNORE INTO `oxpayments` (`OXID`, `OXACTIVE`, `OXDESC`, `OXTOAMOUNT`, `OXVALDESC`, `OXVALDESC_1`, `OXVALDESC_2`, `OXVALDESC_3`, `OXLONGDESC`, `OXLONGDESC_1`, `OXLONGDESC_2`, `OXLONGDESC_3`) VALUES
('oxshopgate', '1', 'Shopgate', '1000000', 'shopgate', '', '', '', 'Bezahlt bei Shopgate', '', '', ''),
('oxmobile_payment', '1', 'Mobile Payment', '100000', 'mobile payment', '', '', '', 'Bezahlt mit mobiler App', '', '', '');

ALTER TABLE `oxdeliveryset` ADD COLUMN `shopgate_service_id` VARCHAR(100) NULL;
INSERT IGNORE INTO `oxdeliveryset` (`OXID`, `OXSHOPID`, `OXACTIVE`, `OXTITLE`) VALUES ('mobile_shipping',  '',  '0', 'Mobile Shipping (Shopgate)');

ALTER TABLE `oxactions` ADD COLUMN `shopgate_is_highlight` BOOLEAN NOT NULL DEFAULT  '0';

CREATE TABLE IF NOT EXISTS `oxordershopgate` (
 `OXID` char(32) NOT NULL,
 `OXORDERID` char(32) NOT NULL,
 `order_number` varchar(20) NOT NULL,
 `is_sent_to_shopgate` tinyint(1) NOT NULL DEFAULT '0',
 `is_paid` tinyint(1) NOT NULL DEFAULT '0',
 `is_shipping_blocked` tinyint(1) NOT NULL DEFAULT '1',
 `is_cancellation_sent_to_shopgate` tinyint(1) NOT NULL DEFAULT '0',
 `reported_cancellations` TEXT,
 `order_data` TEXT,
 PRIMARY KEY (`OXID`)
) ENGINE=InnoDB;
