
ALTER TABLE  `oxdeliveryset` ADD COLUMN  `shopgate_service_id` VARCHAR( 100 ) NULL;
INSERT INTO `oxdeliveryset` ( `OXID` , `OXSHOPID` , `OXACTIVE` , `OXTITLE` ) VALUES ( 'mobile_shipping',  '',  '0', 'Mobile Shipping (Shopgate)' );