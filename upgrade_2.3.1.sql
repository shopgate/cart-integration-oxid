
ALTER TABLE `oxordershopgate` ADD `is_cancellation_sent_to_shopgate` tinyint(1) NOT NULL DEFAULT '0' AFTER `is_shipping_blocked`;
ALTER TABLE `oxordershopgate` ADD `reported_cancellations` TEXT NOT NULL DEFAULT '' AFTER `is_cancellation_sent_to_shopgate`;

ALTER TABLE  `oxpayments` ADD COLUMN  `shopgate_payment_method` VARCHAR( 100 ) NULL;