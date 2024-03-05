# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Fixed
- PHP warnings by accessing undefined constant `ADODB_FETCH_ASSOC`
- installation can now be resumed after it was aborted during installing database schemas

### Changed
- uses Shopgate Cart Integration SDK v2.10.3

### Added
- compatibility with Oxid CE up to 6.5 included
- compatibility with PHP 7 & 8
- enabled installation via Composer / packagist.org if desired

## [2.9.78] - 2018-03-28
### Fixed
- missing taxes on child products during item export
- redirect to product detail view not working in order history view
- product images not shown in order history view

## [2.9.77] - 2018-08-02
### Added
- compatibility with Oxid 6

## [2.9.76] - 2018-03-13
### Fixed
- Shopgate coupon issue related to Oxid Enterprise

## [2.9.75] - 2018-01-23
### Fixed
- export of reviews for child products
- stopped export of inactive reviews

## [2.9.74] - 2017-11-29
### Fixed
- export of product relations for child products

## 2.9.73
### Changed
- Added method to directly set Oxid order total to be equal to Shopgate order total

## 2.9.72
### Changed
- migrated Shopgate integration for Oxid to GitHub

### Fixed
- Shopgate coupons in orders are now transferred as net when shop is configured to use net prices

## 2.9.71
- guest accounts are imported as active from now on
- uses Shopgate Library 2.9.66

## 2.9.70
- fixed issue in method set_settings for unknown oxid configuration fields
- fixed issue with missing shopgate coupons in imported orders

## 2.9.69
- fixed issue with orders being imported multiple times
- added default user group "oxidnotyetordered" for guests
- fixed issue with Shopgate coupons being active longer than expected
- added the correct sequence number for future Payone paid orders

## 2.9.68
- fixed a bug in the redirection of CMS pages
- fixed plugin shipping in Oxid 4.5.x and 4.9.x

## 2.9.67
- fixed order total issue
- uses Shopgate Library 2.9.63
- added payment method constant for merchant payment

## 2.9.66
- restored compatibility for PHP versions below 5.3
- uses Shopgate Library 2.9.62

## 2.9.65
- added default user group "oxidnotyetordered" on register_customer

## 2.9.64
- native support of Payone payment methods (at least credit card, paypal and sofort)
- fixed issue related to third party modules when importing order
- fixed tags export

## 2.9.63
- activated and fixed cms redirect
- uses Shopgate Library 2.9.58

## 2.9.62
- fixed rounding error in customer group tier prices export
- fixed shopgate coupon handling in Oxid Enterprise Edition 5.2

## 2.9.61
- fixed bug with missing product reviews

## 2.9.60
- hidden categories won't be redirected any longer

## 2.9.59
- fixed exporting duplicate tax rates

## 2.9.58
- fix malicious tax export in case there was a default tax rate set in Oxid configuration

## 2.9.57
- fix malicious tax export in case there was no default tax rate set in Oxid configuration
- fix missing weight on child products

## 2.9.56
- fixed error when building redirect for not existing manufacturers
- Updated Library to 2.9.47

## 2.9.55
- now always exporting tax rates for home countries
- fixed exporting the proper stock availability text
- fixed product export failure when a corrupt product is present in the 'oxarticles' table

## 2.9.54
- check_cart sometimes reused coupons that had already been used on an order

## 2.9.53
- fixed some minor issues in settings export
- check_cart didn't always return the correct shipping costs
- fixed available text in product export
- fixed small issue in product stock check

## 2.9.52
- fixed a bug that caused items to be not found in cart validation
- exporting net prices with a higher precision (4 decimal places instead of 2) to avoid rounding errors
- order import: payment fees were not always imported correctly, which could lead to a wrong total sum

## 2.9.51
- order import: payment method Billsafe is now mapped correctly

## 2.9.50
- improved export of items which are sold in packages

## 2.9.49
- fixed a bug with missing ean and sku for child products in xml export

## 2.9.48
- fixed a bug that could cause the plugin to crash in older Oxid versions

## 2.9.47
- fixed a bug in add_order that occurred in older versions of Oxid

## 2.9.46
- Product export: custom field "tc_isbuyable" is now taken into account (if present)
- Orders were imported with wrong VAT amount, if the shop uses net prices

## 2.9.45
- added support for entering net prices in the Oxid backend

## 2.9.44
- fixed a bug that could cause check_cart to crash in certain cases
- check_cart now always cleans up temporarily created users

## 2.9.43
- check_cart now also returns payment methods

## 2.9.42
- Updated Library to 2.9.35
- native support of Paypal Plus payments

## 2.9.41
- Product export/tier prices: in certain Oxid versions only the first tier was exported

## 2.9.40
- Orders containing multiples coupons were not imported correctly under certain circumstances

## 2.9.39
- implemented redirect type "search"
- check_cart now marks coupons as reserved
- fixed a bug that could break the checkout "thankyou" page

## 2.9.38
- Product export now also supports customer group prices (A,B,C)
- Product export: tier prices weren't calculated correctly if there was a discount

## 2.9.37
- fixed a bug that could cause the product export to crash in certain cases

## 2.9.36
- Product export: "Available on" date is now taken into account when generating the availability text
- check for and create missing database columns in every request
- Product export: descriptions were not exported for child products
- Product export: removed MS-Outlook-specific code from descriptions
- Order import: eMails to the customer and the merchant can now be turned on and off separately

## 2.9.35
- Updated Library to 2.9.24
- added lots of missing Shopgate payment methods for manual mapping

## 2.9.34
- Updated Library to 2.9.22
- removed German changelog
- native support of Payolution payments
- fixed a bug in order import
- product export: availability text was wrong in certain cases

## 2.9.33
- CMS redirect deactivated

## 2.9.32
- Updated Library to 2.9.19

## 2.9.31
- Updated Library to 2.9.18

## 2.9.30
- Updated Library to 2.9.16
- settings export: tax rates are now rounded to a maximum of 4 decimal digits
- Product export: prices were sometimes still rounded (by Oxid itself)
- Product export (XML): MSRP, tier prices & purchase price ("cost") were exported as gross instead of net

## 2.9.29
- Fixed a bug that broke compatibility with PHP 5.2
- set_settings: some values that are inherited from the library can now also be set
- Product export (XML): "was price" was exported as gross instead of net
- Product export: prices don't get rounded anymore
- Product export (XML): additional price for options was exported as gross instead of net

## 2.9.28
- Product export: implemented sorting by custom date fields
- Product export (XML): selection lists were not exported

## 2.9.27
- Product export: availability text wasn't always exoported correctly
- Product export (XML): tax_class + tax_percent are now both exported (since tax_percent must not be empty)
- Product export (XML): fixed a bug in exporting upsell / cross-sell relations

## 2.9.26
- Product export (XML): fixed a bug concerning attributes

## 2.9.25
- Product export (XML): fixed a bug concerning tier prices
- Product export (XML): added identifier "SKU"

## 2.9.24
- add_order: surcharge/discount for payment type wasn't always set correctly

## 2.9.23
- payment type "Paymorrow" can now be mapped manually
- bugfix in add_order

## 2.9.22
- add_order now checks whether the ordered articles still exist in the database

## 2.9.21
- hidden categories now also get exported

## 2.9.20
- Updated Library to 2.9.11

## 2.9.19''
- DB views weren't updated when installing the plugin

## 2.9.18
- get_settings: tax rules are now summarized into groups

## 2.9.17
- fixed a bug in add_order (duplicate entry on insert into oxuser)
- check_cart/check_stock: The last item on stock could not be ordered.

## 2.9.16
- add_order: improved logging in case of an exception

## 2.9.15
- Product export: base_price was missing in XML
- Product export: base_price is now exported in units of "100ml" + "100g" (instead of "ml" + "g")

## 2.9.14
- Payment type 'Store pickup' can now be mapped manually

## 2.9.13
- add_order: improved logging in case a coupon could not be added to the order
- check_cart: Umlauts in error message were scrambled when a coupon was invalid

## 2.9.12
- Product export: link between product + category was missing in certain cases

## 2.9.11
- add_order/register_customer: always set oxshopid when creating a user

## 2.9.10
- get_customer: Exceptions other than "wrong password" should be logged
- Admin URL may not be used as API URL

## 2.9.9
- Product export (CSV): removed block_pricing (not supported by the Shopgate backend)
- Product export (XML): added tier prices

## 2.9.8
- order custom fields now shown in the Shopgate tab and mapped to oxid db columns, if possible

## 2.9.7
- implemented XML export (for products/categories/reviews)
- made "enable_default_redirect" an invisible config flag

## 2.9.6
- direct usage of oxSession::getVar()/setVar() led to a fatal error in Oxid 4.9

## 2.9.5
- get_settings: implemented fields "allowed_address_countries" + "allowed_shipping_countries"
- check_cart: shipping cost amount was formatted incorrectly
- payment type "Amazon Payment" can now be mapped manually
- add_order: fixed a bug that could lead to the field "oxshopid" being unset in the user account
- add_order: improved logging in case of an exception

## 2.9.4
- add_order: fixed a bug in coupon amount calculation
- check_cart: sort order was missing in shipping mehtods

## 2.9.3
- Updated Library to 2.9.3
- implemented get_reviews
- add_order: some exceptions thrown by oxid were uncaught
- add_order doesn't try to import orders anymore if the plugin isn't activated

## 2.9.2
- Updated Library to 2.9.2
- implemented get_orders
- Fixed a bug in mobile redirect
- The product export failed in cases which didn't have any highlights to export
- check_cart: field "amount" wasn't set in shipping methods

## 2.9.1
- Fixed a bug in shipping cron job
- check_cart: fixed a bug concerning the handling of user accounts

## 2.9.0
- Updated Library to 2.9.1
- add_order: ignore non-numeric return values from finalizeOrder()

## 2.8.20
- modified mobile redirect so that category pages built on a certain fatchip plugin are also redirected
- fixed a bug that could possibly cause newly created products to not have the "export to shopgate" flag set in older Oxid versions

## 2.8.19
- mobile redirect: refactored and added a log statement in order to be able to debug faulty redirects

## 2.8.18
- finalizeOrder() doesn't execute any payments in an add_order request anymore. (This caused problems with Paypal orders.)

## 2.8.17
- implemented check_stock
- redeem_coupon failed when no delivery address was present

## 2.8.16
- add_order: packaging unit prices still weren't calculated correctly

## 2.8.15
- add_order: article prices were zero under certain circumstances

## 2.8.14
- improved compatibility with Oxid 4.9.x

## 2.8.13
- add_order: phone number was missing
- cosntant SHOPGATE_PLUGIN_VERSION is now only declared once

## 2.8.12
- add_order: packaging unit prices weren't calculated correctly

## 2.8.11
- implemented get_reviews_csv
- preparations for XML eport

## 2.8.10
- Ping now also returns data about the plugin health

## 2.8.9
- fixed a typo in metadata.php

## 2.8.8
- ping() nwo also returns installed plugins
- improved compatibility with Oxid 4.9.x
- bugfix: submitting an order's shipping status to Shopgate didn't work properly

## 2.8.7
- bugfix in register_customer: only partial user was created in some cases

## 2.8.6
- various bugfixes (Undefined index/property of non-object)

## 2.8.5
- implemented check_cart item validation
- removed the option to export options as attributes

## 2.8.4
- Updated Library to 2.8.10

## 2.8.3
- addOrder(): wrong payment type was shown in Oxid's order confirmation mail

## 2.8.2
- Bugfix in checkCart shipping cost calculation

## 2.8.1
- getCustomer() can now return multiple customer groups

## 2.8.0
- Updated Library to 2.8.4
- compatibility with Oxid 4.1.x
- compatibility with Oxid 4.9.x
- checkCart(): shipping cost calculation didn't work under certain circumstances
- refactoring

## 2.7.4
- product export: values with umlauts weren't exported if Oxid is installed in ISO mode
- product export: field "categories" isn't exported anymore since it's deprecated
- product export: minor performance improvements
- implemented createShopInfo()
- refactoring

## 2.7.3
- product export: improved logging
- product export: increased PHP's max_execution_time to 10 min

## 2.7.2
- faulty module entry fixed

## 2.7.1
- checkCart() now returns shipping methods

## 2.7.0
- Updated Library to 2.7.0
- implemented register_customer

## 2.4.30
- addOrder(): orders with a free shipping coupon could not be imported

## 2.4.29
- product export: minimum/maximum order quantity must be divided by packaging unit

## 2.4.28
- product export: an article's minimum/maximum order quantity are now being exported

## 2.4.27
- fixed a syntax error

## 2.4.26
- addOrder(): some third party plugins could cause orders to be not imported correctly

## 2.4.25
- bugfix in InstallHelper

## 2.4.24
- Options don't need to be exported for parent articles

## 2.4.23
- new config flag for suppressing order notes

## 2.4.22
- checkCart(): calculation of coupon value was too complex without necessity

## 2.4.21
- product export: dimensions are now also being exported (in the properties)

## 2.4.20
- Prevent other plugins from overwriting our delivery costs in an order.

## 2.4.19
- getCustomer(): country + state values were missing in addresses
- Mobile redirect for products didn't work properly under certain circumstances
- checkCart(): error message for an invalid coupon was "null"
- Moved class ShopgatePluginOxidEE into separate file
- Various minor bugfixes

## 2.4.18
- InstallHelper: updating the database views didn't work properly
- a missing column in an SQL query could cause problems in product export

## 2.4.17
- Mobile Header should not be inserted into order confirmation mails

## 2.4.16
- Oxid 4.2 compatibility

## 2.4.15
- fixed a bug in mobile redirect for brand pages

## 2.4.14
- bugfix: rewritten a require_once command, which could not work in older oxid versions

## 2.4.13
- bugfix: Code contained syntax which is only supported in PHP 5.4 and above

## 2.4.12
- install.sql script is now executed automatically

## 2.4.11
- Updated Library to version 2.4.16
- Bugfix: an exception wasn't caught properly

## 2.4.10
- Updated Library to version 2.4.14
- cron: improved error handling

## 2.4.9
- addOrder(): improved error handling

## 2.4.8
- Updated Library to version 2.4.13
- Workaround for a bug in Oxid < 4.7.1, which caused parent article's properties to not get inherited by children.

## 2.4.7
- Updated Library to version 2.4.12

## 2.4.6
- Article variants without a name can now be imported.
- When trying to import an order twice, the OXID of the existing order is now appended to the error message.

## 2.4.5
- Updated Library to version 2.4.6
- added various payment types

## 2.4.4
- Improved logging in certain cases

## 2.4.3
- Workaround for a Bug in Oxid < 4.7.1 that could mess up the voucher calculation, if oxBasket->calculateBasket() is called multiple times.

## 2.4.2
- Fixed a bug that could cause mobile redirect to not function properly with parent products.
- Products that are not being exported to Shopgate shouldn't be redirected to the mobile website either.
- Paypal transaction id is now set in oxorder

## 2.4.1
- Implemented some debugging features in get_items_csv.
- Added a setting to determine whether "parent products" should be orderable in the Shopgate shop.

## 2.4.0
- Updated Library to version 2.4.0
- Implemented the API function set_settings.
- Fixed a bug that could cause an article's short description to be exported with the label "OXSHORTDESC" (instead of "Short description").

## 2.3.9
- Fixed a bug that caused unnecessary Shopgate Merchant API calls.
- Order import: eliminated a possible error source when setting delivery type.

## 2.3.8
- The Shopgate payment type Paypal is now correctly assigned to the corresponding Oxid payment type.
- Fixed a bug concerning mobile redirect.

## 2.3.7
- New setting for how to export article names: name / short description / name + description

## 2.3.6
- Detection of default article image (nopic.jpg) was still erroneous.

## 2.3.5
- Fixed a bug that could cause oxid's encoding setting to be interpreted incorrectly.

## 2.3.4
- Fixed a bug in get_settings() because of which tax rules could not be imported properly.

## 2.3.3
- Orders were missing the VAT percentage of the delivery costs.

## 2.3.2
- action 'get_settings' now returns information about tax usage in oxid shop
- manufacturer pages redirect to mobile version
- redirect to not defined pages can now be deactivated
- fixed potential error source in getShippingServiceId(): "Invalid argument supplied for foreach()" could occur under certain circumstances
- Oxid's default article image (nopic.jpg) shouldn't be exported
- Fixed a bug that could cause orders to be imported without VAT.

## 2.3.1
- Updated Library to version 2.3.2
- payment methods in oxid can now mapped to Shopgate payment methods
- cancellations can now sent from oxid to Shopgate

## 2.3.0
- Updated Library to version 2.3.1
- export sort of products in a category
- ordered articles includes tax

## 2.2.11
- fix bug on use coupons and export products in OXID 4.3
- remove use of a oxid function which does not exist in OXID < 4.5
- getCustomer (ShopgateConnect) returns more information about the customer now
- fix calculation bug for payment fees
- action articles can now export as is_highlight

## 2.2.10
- fix a problem with oxid coupons
- check for empty OXID on try to load an existing user

## 2.2.9
- Updated Library to version 2.2.2
- disable oxid validation of Shopgate orders
- fix import of customizable products
- Shopgate setting for "folders" is now dropdown, not textbox anymore
- no oxaddress entry will added if delivery address is the same as invoice address
- shopgate plugin cache is now in tmp/shopgate/<shopnumber>

## 2.2.8
- customizable products will be export again
- select delivery set also by given shipping country

## 2.2.7
- fix bug in order process

## 2.2.6
- Updated Library to version 2.2.0
- shipping method "Mobile Shipping (Shopgate)" added
- shipping methods from Shopgate can map to shipping method in oxid
- try to added phone number to user and addresses if one is received
- discount prices in orders fixed in oxid 4.6.x

## 2.2.5
- export sort order of categories
- Fix import of orders with product options
- oxid orders can link to or unlink from Shopgate orders. For example if order was capture manually
- save Shopgate order after finalize oxid order

## 2.2.4
- fixed issue with SEO url_deeplink
- export parent product as saleable if activated in oxid
- export sort order of categories
- ignore A/B/C prices on add new order

## 2.2.3
- fixed issue on export (on selection "All as attribute")
- fixed issue on importing order with a guest account
- fixed issue for oxid 4.3.x and 4.4.x. The number of articles in a order was sometimes not correct
- fixed issue with the new coupon system. Do not mark them as reserved if they don't should

## 2.2.2
- export article number by default instead of oxid
- ignore payment settings on add order

## 2.2.1
- fix bug at add order with discount prices. In the order view the prices
  shows with double discount but paid amount was correct
- added option to select export language

## 2.2.0
- Updated Library to version 2.2.0
- implement support for new functions check_cart and redeem_coupons. Now it is possible to redeem coupons from mobile devices
- remove unused function 'loadOrderNumber'
- fix bugs for oxid 4.3. Now the basket will not save any more on shopgate requests
- fix bug on export article description for oxid < 4.6

## 2.1.40
- bugfix adding additional display prices on selected variations

## 2.1.39
- Updated Library to version 2.1.26
- articles which are set to "If out of stock, offline" will add to the system nevertheless
- remove log messages

## 2.1.38
- check support for older oxid versions (>= 4.3.0) and restore it

## 2.1.37
- fix error on calculating total sum of order
- sales price will show in oxid order and not the current price from database

## 2.1.36
- try to create unique article numbers on export by adding options as suffix to it
- better handling errors on compile smarty tags in description
- bugfixes

## 2.1.35
- fix bug od double increment customer number

## 2.1.34
- Updated Library to version 2.1.25
- fixed bug in redirect on OXID > 4.7.3
- redirect to cms pages

## 2.1.33
- Updated Library to version 2.1.24
- canceled orders can be edited again
- do not try to redirect on ajax-requests
- compare existing addresses with coutry_id too

## 2.1.32
- fix not available text
- show correct prices in order mail

## 2.1.31
- Updated Library to version 2.1.23
- fix problem with oxuser__oxustidstatus on OxidEE

## 2.1.30
- BugFixes
- return database charset in ping action

## 2.1.29
- fix bug for shipping costs
- ignore stock check on add order

## 2.1.28
- Updated Library to version 2.1.22
- fix some bugs for oxidEE
- clean category url on export

## 2.1.27
- Updated Library to version 2.1.21

## 2.1.26
- BugFix
- Calculate shipping tax

## 2.1.25
- change folder structure for oxid > 4.6
- article identifier change in config (id / articlenumber)
- bug fixes

## 2.1.24
- fixed some bugs in the new order organization

## 2.1.23
- bugfix for msrp
- order will be import as a simulate basket. Now the default order mail can send from oxid to the user
- country state is added to order
- clean up article url. A parameter sid/force_sid was exported

## 2.1.22
- on order import keep article number empty if no given
- apply used oxid config fields to shopgate config

## 2.1.21
- bug fix cron-job

## 2.1.20
- send trackingcode to shopgate
- payment method shopgate and mobile_payment can be select in shopgate orders
- show state of a order if shippings is commited to shopgate

## 2.1.19
- fixed metadata.php

## 2.1.18
- fixed available text

## 2.1.17
- fixed encoding

## 2.1.16
- export of article selections will be active as default
- fixed available text
- fixed encoding of orders
- special-prices will export as it is

## 2.1.15
- Option to sync order manually

## 2.1.14
- fixed the issue where redirection to "http://" was performed if no CNAME was set
- uses Shopgate Library 2.1.17

## 2.1.13
- Updated Library to 2.1.16
- street and house number will be saved in it separate fields
- remove mobile header in order mails
- option to select shipping service on order import

## 2.1.12
- lock ship-button if order is blocked by shopgate
- Updated Library to 2.1.15

## 2.1.11
- better information of delivery and stock

## 2.1.10
- set oxartnum if no exists
- delete oxordershopgate entry on delete oxorder
- fix set shipping complete

## 2.1.9
- Remarks will only display if shipping of the order is blocked by Shopgate
- workaround for OXID 4.7.0 - ordernumbers must be generated manually
- cron to clean up shopgate orders

## 2.1.8
- separate tab for shopgate details to the current order

## 2.1.7
- BugFix

## 2.1.6
- Updated Library to 2.1.11
- Mobile Website with PHP-Header or JavaScript

## 2.1.5
- compatibility with OXID 4.7.0/5.0.0
- orders can be placed into different order folder
- addOrder and updateOrder returns id and number for the inserted order
- active_status will be set in export. Out of stock articles can be show

## 2.1.4
- Updated Library to 2.1.6 to (replace is_active by active_status)
- Fix Bug on export VPE in OxidEE
- Fix Bug on export manufacturer

## 2.1.3
- marm_shopgate_oxarticle for overloading Module in less than OXID 4.4.7 to
  export products to shopgate as default

## 2.1.2
- Updated Library to 2.1.4 to fix an internal error

## 2.1.1
- Updated Library to 2.1.1 to fix bug for PHP < 5.3

## 2.1.0
- New Library 2.1.0

## 2.0.17
- added payment method mobile_payment (oxempty was used before)
- Updated Library to version 2.0.29
- added filter for mobile redirect

## 2.0.16
- fetch order_already_completed error to ignore

## 2.0.15
- cron-action added to send order confirmations to Shopgate
- female as default gender if not given

## 2.0.14
- Bug Fixes

## 2.0.13
- Fix Image export

## 2.0.9
- Option to ignore Articles on Shopgate Export

## 2.0.7
- Bug Fixes

## 2.0.6
- BugFixes in Order Import
- Orders will import in Database Transaction
- Fixed Bug if Variation has no title

## 2.0.5
- Export all Options as Attributes to solve problem if article have selection AND variations
- Fixed Bug in Export Selection Prices
- Testet new OXID-Version 4.6.1


## 2.0.4
- Mobile Website with PHP redirect-code
- Testet new OXID-Version 4.6.0


## 2.0.0
- New Release for this module

[Unreleased]: https://github.com/shopgate/cart-integration-oxid/compare/2.9.78...HEAD
[2.9.78]: https://github.com/shopgate/cart-integration-oxid/compare/2.9.77...2.9.78
[2.9.77]: https://github.com/shopgate/cart-integration-oxid/compare/2.9.76...2.9.77
[2.9.76]: https://github.com/shopgate/cart-integration-oxid/compare/2.9.75...2.9.76
[2.9.75]: https://github.com/shopgate/cart-integration-oxid/compare/2.9.74...2.9.75
[2.9.74]: https://github.com/shopgate/cart-integration-oxid/compare/2.9.73...2.9.74
[2.9.73]: https://github.com/shopgate/cart-integration-oxid/compare/2.9.72...2.9.73
