
CREATE TABLE IF NOT EXISTS `oxordershopgate` (
 `OXID` char(32) NOT NULL,
 `OXORDERID` char(32) NOT NULL,
 `order_number` varchar(20) NOT NULL,
 `is_sent_to_shopgate` tinyint(1) NOT NULL DEFAULT '0',
 `is_paid` tinyint(1) NOT NULL DEFAULT '0',
 `is_shipping_blocked` tinyint(1) NOT NULL DEFAULT '1',
 `order_data` text,
 PRIMARY KEY (`OXID`)
) ENGINE=InnoDB;

INSERT IGNORE INTO `oxordershopgate` (`OXID`,`OXORDERID`,`order_number`,`is_sent_to_shopgate`,`is_paid`)
SELECT `OXID`, `OXID`, `marm_shopgate_order_number`, `marm_is_sent_to_shopgate`, STRCMP( UNIX_TIMESTAMP( oxpaid ) , 0 )
FROM oxorder WHERE `marm_shopgate_order_number` != 0;
