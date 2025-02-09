<?php

/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

$sLangName = "English";

$aLang = array(
    'charset'                   => 'ISO-8859-15',
    'tbclmarm_shopgate_config'  => 'Shopgate',
    'tbclmarm_shopgate_article' => 'Shopgate',
    'tbclshopgate_actions'      => 'Shopgate',
    'tbclshopgate_order'        => 'Shopgate',
    'tbclshopgate_shipping'     => 'Shopgate',
    'tbclshopgate_payment'      => 'Shopgate',

    'SHOPGATE_ARTICLE_EXPORT'           => 'Export product to Shopgate',
    'SHOPGATE_ARTICLE_EXPORT_HELP'      => 'Export product to Shopgate.',
    'SHOPGATE_ARTICLE_MARKETPLACE'      => 'Show product in Shopgate marketplace',
    'SHOPGATE_ARTICLE_MARKETPLACE_HELP' => 'Show product in Shopgate marketplace',

    'SHOPGATE_ORDER_ORDER'                    => 'Order',
    'SHOPGATE_ORDER_CUSTOM_FIELDS'            => 'Custom Fields',
    'SHOPGATE_ORDER_CUSTOM_FIELDS_INVOICE'    => 'Custom Fields (Invoice Address)',
    'SHOPGATE_ORDER_CUSTOM_FIELDS_DELIVERY'   => 'Custom Fields (Delivery Address)',
    'SHOPGATE_ORDER_PAYMENT_INFOS'            => 'Payment',
    'SHOPGATE_ORDER_NO_SHOPGATE_ORDER'        => 'This is not a Shopgate order...',
    'SHOPGATE_ORDER_ORDER_NUMBER'             => 'Order number',
    'SHOPGATE_ORDER_TRANSACTIONNUMBER'        => 'Transactionnumber',
    'SHOPGATE_ORDER_PAYMENT_TYPE'             => 'Payment type',
    'SHOPGATE_ORDER_IS_PAID'                  => 'Is paid',
    'SHOPGATE_ORDER_IS_SHIPPING_BLOCKED'      => 'Shipping is blocked',
    'SHOPGATE_ORDER_IS_SHIPPING_BLOCKED_YES'  => 'The goods must not be shipped currently!',
    'SHOPGATE_ORDER_IS_INVOICE_BLOCKED'       => 'Insert invoice',
    'SHOPGATE_ORDER_IS_INVOICE_BLOCKED_YES'   => 'Do not attach an invoice to this order!',
    'SHOPGATE_IS_SENT_TO_SHOPGATE'            => 'Shipping state is commited to Shopgate',
    'SHOPGATE_SURE_RESET_SENT_STATE'          => 'Are you sure you want to reset this state?',
    'SHOPGATE_ORDER_LINK_TO_A_SHOPGATE_ORDER' => 'Link to a Shopgate order',
    'SHOPGATE_ORDER_UNLINK_ORDER'             => 'Unlink order',
    'SHOPGATE_ORDER_UNLINK_ORDER_CONFIRM'     => 'Are you sure to delete this link?',

    'SHOPGATE_SHIPPING_NOT_POSSIBLE'   => 'Assigning to this shipping method is not possible!',
    'SHOPGATE_SHIPPING_SELECT_SERVICE' => 'Choose shipping key',

    'SHOPGATE_ACTIONS_IS_HIGHLIGHT' => 'Export articles as highlight',

    'SHOPGATE_CONFIG_GROUP_GENERAL'                      => 'Basic Configuration',
    'SHOPGATE_CONFIG_GROUP_ORDERS'                       => 'Orders',
    'SHOPGATE_CONFIG_GROUP_MOBILEWEB'                    => 'Mobile website',
    'SHOPGATE_CONFIG_GROUP_EXPORT'                       => 'Export',
    'SHOPGATE_CONFIG_GROUP_DEBUG'                        => 'DEBUG',

    // Group general
    'SHOPGATE_CONFIG_SHOP_IS_ACTIVE'                     => 'Is your shop activated at Shopgate?',
    'SHOPGATE_CONFIG_CUSTOMER_NUMBER'                    => 'Shopgate Customer Number',
    'SHOPGATE_CONFIG_CUSTOMER_NUMBER_HELP'               => 'Your customer number at Shopgate',
    'SHOPGATE_CONFIG_SHOP_NUMBER'                        => 'Shop Number',
    'SHOPGATE_CONFIG_SHOP_NUMBER_HELP'                   => 'Your shop number at Shopgate',
    'SHOPGATE_CONFIG_APIKEY'                             => 'Shopgate API Key',
    'SHOPGATE_CONFIG_APIKEY_HELP'                        => 'Your personal API key. You can find this in your merchant settings at Master Data, under the tab API key. <a href="https://www.shopgate.com/merchant/apikey" target="_blank"> https://www.shopgate.com/merchant/apikey </a> ',

    // Group orders
    'SHOPGATE_CONFIG_UNBLOCKED_ORDERS_AS_PAID'           => 'Mark unblocked orders as paid',
    'SHOPGATE_CONFIG_UNBLOCKED_ORDERS_AS_PAID_HELP'      => '<strong>Only for orders with payment method \'Shopgate\'</strong><br/>Mark unblocked orders as paid in OXID. At Shopgate the order is still marked as unpaid.<br /> Example: Invoice payment',
    'SHOPGATE_CONFIG_ORDERFOLDER_BLOCKED'                => 'Folder for blocked orders',
    'SHOPGATE_CONFIG_ORDERFOLDER_BLOCKED_HELP'           => 'Orders are placed in this folder if the shipping is blocked by Shopgate.',
    'SHOPGATE_CONFIG_ORDERFOLDER_UNBLOCKED'              => 'Folder for unblocked Orders',
    'SHOPGATE_CONFIG_ORDERFOLDER_UNBLOCKED_HELP'         => 'Orders are placed in this folder if shipping is not blocked by shopgate.<br /><strong>CAUTION::</strong> If payment method is not \'Shopgate\' please check the payment status manually.',
    'SHOPGATE_CONFIG_DELIVERY_SERVICE'                   => 'Delivery service for Shopgate orders',
    'SHOPGATE_CONFIG_SEND_MAILS'                         => 'Send eMails to customer',
    'SHOPGATE_CONFIG_SEND_MAILS_HELP'                    => 'Should OXID send eMails about Shopgate orders to the customer?',
    'SHOPGATE_CONFIG_SEND_MAILS_TO_OWNER'                => 'Send eMails to store owner',
    'SHOPGATE_CONFIG_SEND_MAILS_TO_OWNER_HELP'           => 'Should OXID send eMails about Shopgate orders to the store owner?',
    'SHOPGATE_CONFIG_SUPPRESS_ORDER_NOTES'               => 'Suppress automatic notes in the order.',

    // Group mobile
    'SHOPGATE_CONFIG_ENABLE_MOBILE_WEBSITE'              => 'Activate mobile website',
    'SHOPGATE_CONFIG_ENABLE_MOBILE_WEBSITE_HELP'         => '',
    'SHOPGATE_CONFIG_CNAME'                              => 'CNAME',
    'SHOPGATE_CONFIG_CNAME_HELP'                         => 'A cname is a reference to another website. This entry must be set up at your web hoster where your domain is registered.<br/><strong>Example:</strong>http://m.myshop.com',
    'SHOPGATE_CONFIG_ALIAS'                              => 'Alias',
    'SHOPGATE_CONFIG_ALIAS_HELP'                         => 'You can find the alias in the Shopgate settings.<br /><strong>Example:</strong> myshop',
    'SHOPGATE_CONFIG_LANGUAGES'                          => 'Language',
    'SHOPGATE_CONFIG_LANGUAGES_HELP'                     => 'Enter a comma-separated list of languages that should be redirected to your mobile website.<br /><strong>Example:</strong> de, en, fr<br /><br /><strong>Leave blank to redirect all languages</strong>',
    'SHOPGATE_CONFIG_REDIRECT_TYPE'                      => 'Type',
    'SHOPGATE_CONFIG_REDIRECT_TYPE_HEADER'               => 'HTTP-Header',
    'SHOPGATE_CONFIG_REDIRECT_TYPE_JAVASCRIPT'           => 'JavaScript',
    'SHOPGATE_CONFIG_REDIRECT_TYPE_HELP'                 => 'How to redirect to your mobile shop.<br /><strong>HTTP-Header</strong>: The customer will be redirected before the page is loaded.<br /><br /><strong>JavaScript</strong>: A JavaScript snippet is inserted into your page.',
    'SHOPGATE_CONFIG_ENABLE_DEFAULT_REDIRECT'            => 'Redirect all pages',
    'SHOPGATE_CONFIG_ENABLE_DEFAULT_REDIRECT_HELP'       => 'The mobile webpage is activated for this page type: <b>start</b>, <b>product</b>, <b>category</b>, <b>manufacturer</b>.<br/><br/>If <b>ALL</b> pages should redirect to mobile webpage than activte this option.',

    // Group export
    'SHOPGATE_CONFIG_LANGUAGE'                           => 'Language',
    'SHOPGATE_CONFIG_ARTICLE_IDENTIFIER'                 => 'Article identifier',
    'SHOPGATE_CONFIG_ARTICLE_IDENTIFIER_HELP'            => 'Please only change this option if you are sure that every article has a unique article number!',
    'SHOPGATE_CONFIG_ARTICLE_IDENTIFIER_OXID'            => 'ArticleID',
    'SHOPGATE_CONFIG_ARTICLE_IDENTIFIER_OXARTNUM'        => 'Article number',
    'SHOPGATE_CONFIG_ARTICLE_NAME_EXPORT_TYPE'           => 'Article name',
    'SHOPGATE_CONFIG_ARTICLE_NAME_EXPORT_TYPE_HELP'      => 'Which of these Oxid values should be used as article name in your Shopgate Shop?',
    'SHOPGATE_CONFIG_ARTICLE_NAME_EXPORT_TYPE_NAME'      => 'Name',
    'SHOPGATE_CONFIG_ARTICLE_NAME_EXPORT_TYPE_SHORTDESC' => 'Short Description',
    'SHOPGATE_CONFIG_ARTICLE_NAME_EXPORT_TYPE_BOTH'      => 'Name + Short Description',
    'SHOPGATE_CONFIG_VARIANT_PARENT_BUYABLE'             => '"Parent" Products can be purchased',
    'SHOPGATE_CONFIG_VARIANT_PARENT_BUYABLE_HELP'        => 'See Master Settings -> Core Settings -> System -> Variants',
    'SHOPGATE_CONFIG_VARIANT_PARENT_BUYABLE_TRUE'        => 'Yes',
    'SHOPGATE_CONFIG_VARIANT_PARENT_BUYABLE_FALSE'       => 'No',
    'SHOPGATE_CONFIG_VARIANT_PARENT_BUYABLE_OXID'        => 'As in Oxid',

    // Group debug
    'SHOPGATE_CONFIG_SERVER'                             => 'Additional Server',
    'SHOPGATE_CONFIG_SERVER_LIVE'                        => 'Live',
    'SHOPGATE_CONFIG_SERVER_PG'                          => 'Playground',
    'SHOPGATE_CONFIG_SERVER_CUSTOM'                      => 'Custom',
    'SHOPGATE_CONFIG_API_URL'                            => 'Custom API Url',
//		'SHOPGATE_CONFIG_SERVER_HELP'							=> '',

    'SHOPGATE_PAYMENT_SELECT_METHOD' => 'Payment method',
    'SHOPGATE_PAYMENT_NOT_POSSIBLE'  => 'Assigning to this payment method is not possible!',

    'SHOPGATE_PAYMENT_GROUP_CC'         => 'Credit Card',
    'SHOPGATE_PAYMENT_GROUP_CNB'        => 'Click&Buy',
    'SHOPGATE_PAYMENT_GROUP_COD'        => 'Cash on Delivery',
    'SHOPGATE_PAYMENT_GROUP_COLL_STORE' => 'Store pickup',
    'SHOPGATE_PAYMENT_GROUP_DEBIT'      => 'Debit',
    'SHOPGATE_PAYMENT_GROUP_INVOICE'    => 'Invoice',
    'SHOPGATE_PAYMENT_GROUP_MCM'        => 'Mastercard Mobile',
    'SHOPGATE_PAYMENT_GROUP_MWS'        => 'Amazon Payment',
    'SHOPGATE_PAYMENT_GROUP_PAYPAL'     => 'PayPal',
    'SHOPGATE_PAYMENT_GROUP_PAYU'       => 'PayU',
    'SHOPGATE_PAYMENT_GROUP_PREPAY'     => 'Prepayment',
    'SHOPGATE_PAYMENT_GROUP_SUE'        => 'Sofortueberweisung',
    'SHOPGATE_PAYMENT_GROUP_MERCH_PM'   => 'Merchant Payment',

    'SHOPGATE_PAYMENT_METHOD_PREPAY'     => 'Own settlement',
    'SHOPGATE_PAYMENT_METHOD_DEBIT'      => 'Own settlement',
    'SHOPGATE_PAYMENT_METHOD_PAYMRW_DBT' => 'Paymorrow',
    'SHOPGATE_PAYMENT_METHOD_PAYONE_DBT' => 'PAYONE',

    'SHOPGATE_PAYMENT_METHOD_COD'        => 'Own settlement',
    'SHOPGATE_PAYMENT_METHOD_COLL_STORE' => 'Own settlement',

    'SHOPGATE_PAYMENT_METHOD_CC'         => 'Own settlement',
    'SHOPGATE_PAYMENT_METHOD_AUTHN_CC'   => 'Authorize.net',
    'SHOPGATE_PAYMENT_METHOD_BCLEPDQ_CC' => 'Barclays ePDQ (MPI)',
    'SHOPGATE_PAYMENT_METHOD_BNSTRM_CC'  => 'Beanstream',
    'SHOPGATE_PAYMENT_METHOD_BRAINTR_CC' => 'Braintree',
    'SHOPGATE_PAYMENT_METHOD_CHASE_CC'   => 'Chase Paymentech (Orbital)',
    'SHOPGATE_PAYMENT_METHOD_CMPTOP_CC'  => 'Computop',
    'SHOPGATE_PAYMENT_METHOD_CONCAR_CC'  => 'ConCardis',
    'SHOPGATE_PAYMENT_METHOD_CRDSTRM_CC' => 'CardStream',
    'SHOPGATE_PAYMENT_METHOD_CREDITCARD' => 'Kreditkarte -- wird später nach dem Anbieter benannt',
    'SHOPGATE_PAYMENT_METHOD_CYBRSRC_CC' => 'CyberSource',
    'SHOPGATE_PAYMENT_METHOD_DRCPAY_CC'  => 'DirecPay',
    'SHOPGATE_PAYMENT_METHOD_DTCASH_CC'  => 'DataCash',
    'SHOPGATE_PAYMENT_METHOD_DT_CC'      => 'Datatrans',
    'SHOPGATE_PAYMENT_METHOD_EFSNET_CC'  => 'Efsnet',
    'SHOPGATE_PAYMENT_METHOD_ELAVON_CC'  => 'Elavon MyVirtualMerchant',
    'SHOPGATE_PAYMENT_METHOD_EPAY_CC'    => 'ePay',
    'SHOPGATE_PAYMENT_METHOD_EWAY_CC'    => 'eWAY',
    'SHOPGATE_PAYMENT_METHOD_EXACT_CC'   => 'E-Xact',
    'SHOPGATE_PAYMENT_METHOD_FRSTDAT_CC' => 'FirstData US',
    'SHOPGATE_PAYMENT_METHOD_GAMEDAY_CC' => 'Gameday',
    'SHOPGATE_PAYMENT_METHOD_GARANTI_CC' => 'Garanti Sanal POS',
    'SHOPGATE_PAYMENT_METHOD_GESTPAY_CC' => 'GestPay',
    'SHOPGATE_PAYMENT_METHOD_HDLPAY_CC'  => 'HeidelPay',
    'SHOPGATE_PAYMENT_METHOD_HIPAY'      => 'Hipay',
    'SHOPGATE_PAYMENT_METHOD_HITRUST_CC' => 'HiTRUST',
    'SHOPGATE_PAYMENT_METHOD_INSPIRE_CC' => 'Inspire Commerce',
    'SHOPGATE_PAYMENT_METHOD_INSTAP_CC'  => 'InstaPAY',
    'SHOPGATE_PAYMENT_METHOD_INTUIT_CC'  => 'QuickBooks Merchant Services (Intuit)',
    'SHOPGATE_PAYMENT_METHOD_IRIDIUM_CC' => 'Iridium',
    'SHOPGATE_PAYMENT_METHOD_LITLE_CC'   => 'Litle Online',
    'SHOPGATE_PAYMENT_METHOD_MASTPAY_CC' => 'Masterpayment',
    'SHOPGATE_PAYMENT_METHOD_MERESOL_CC' => 'Merchant e-Solutions',
    'SHOPGATE_PAYMENT_METHOD_MERWARE_CC' => 'MerchantWARE',
    'SHOPGATE_PAYMENT_METHOD_MODRPAY_CC' => 'Modern Payments',
    'SHOPGATE_PAYMENT_METHOD_MONERIS_CC' => 'Moneris',
    'SHOPGATE_PAYMENT_METHOD_MSTPAY_CC'  => 'Masterpayment',
    'SHOPGATE_PAYMENT_METHOD_NELTRAX_CC' => 'NELiX TransaX',
    'SHOPGATE_PAYMENT_METHOD_NETBILL_CC' => 'NETbilling',
    'SHOPGATE_PAYMENT_METHOD_NETREGS_CC' => 'NetRegistry',
    'SHOPGATE_PAYMENT_METHOD_NOCHEX_CC'  => 'Nochex',
    'SHOPGATE_PAYMENT_METHOD_OGONE_CC'   => 'Ogone',
    'SHOPGATE_PAYMENT_METHOD_OPTIMAL_CC' => 'Optimal Payments',
    'SHOPGATE_PAYMENT_METHOD_PAY4ONE_CC' => 'Pay4one',
    'SHOPGATE_PAYMENT_METHOD_PAYBOX_CC'  => 'Paybox Direct',
    'SHOPGATE_PAYMENT_METHOD_PAYEXPR_CC' => 'PaymentExpress',
    'SHOPGATE_PAYMENT_METHOD_PAYFAST_CC' => 'PayFast',
    'SHOPGATE_PAYMENT_METHOD_PAYFLOW_CC' => 'PayPal Payflow Pro',
    'SHOPGATE_PAYMENT_METHOD_PAYJUNC_CC' => 'PayJunction',
    'SHOPGATE_PAYMENT_METHOD_PAYONE_CC'  => 'PAYONE',
    'SHOPGATE_PAYMENT_METHOD_PAYZEN_CC'  => 'PayZen',
    'SHOPGATE_PAYMENT_METHOD_PLUGNPL_CC' => 'Plug’n Play',
    'SHOPGATE_PAYMENT_METHOD_PP_WSPP_CC' => 'PayPal Website Payments Pro',
    'SHOPGATE_PAYMENT_METHOD_PSIGATE_CC' => 'Psigate',
    'SHOPGATE_PAYMENT_METHOD_PSL_CC'     => 'PSL Payment Solutions',
    'SHOPGATE_PAYMENT_METHOD_PXPAY_CC'   => 'Payment Express PxPay',
    'SHOPGATE_PAYMENT_METHOD_QUIKPAY_CC' => 'Quickpay',
    'SHOPGATE_PAYMENT_METHOD_REALEX_CC'  => 'Realex',
    'SHOPGATE_PAYMENT_METHOD_SAGEPAY_CC' => 'SagePay',
    'SHOPGATE_PAYMENT_METHOD_SAGE_CC'    => 'Sage Payment Solutions',
    'SHOPGATE_PAYMENT_METHOD_SAMURAI_CC' => 'Samurai',
    'SHOPGATE_PAYMENT_METHOD_SCPTECH_CC' => 'SecurePay Tech',
    'SHOPGATE_PAYMENT_METHOD_SCP_AU_CC'  => 'SecurePay (Australia)',
    'SHOPGATE_PAYMENT_METHOD_SECPAY_CC'  => 'SecurePay',
    'SHOPGATE_PAYMENT_METHOD_SG_CC'      => 'Kreditkarte (Shopgate)',
    'SHOPGATE_PAYMENT_METHOD_SIX_CC'     => 'Six',
    'SHOPGATE_PAYMENT_METHOD_SKIPJCK_CC' => 'Skip Jack',
    'SHOPGATE_PAYMENT_METHOD_SKRILL_CC'  => 'Skrill (Moneybookers)',
    'SHOPGATE_PAYMENT_METHOD_STRIPE_CC'  => 'Stripe',
    'SHOPGATE_PAYMENT_METHOD_TELECSH_CC' => 'Telecash',
    'SHOPGATE_PAYMENT_METHOD_TRNSFST_CC' => 'TransFirst',
    'SHOPGATE_PAYMENT_METHOD_TRUSTCM_CC' => 'Trust Commerce',
    'SHOPGATE_PAYMENT_METHOD_USAEPAY_CC' => 'USA ePay',
    'SHOPGATE_PAYMENT_METHOD_VALITOR_CC' => 'Valitor',
    'SHOPGATE_PAYMENT_METHOD_VERIFI_CC'  => 'Verifi',
    'SHOPGATE_PAYMENT_METHOD_VIAKLIX_CC' => 'ViaKLIX',
    'SHOPGATE_PAYMENT_METHOD_WCARDS_CC'  => 'WireCard Seamless',
    'SHOPGATE_PAYMENT_METHOD_WIRECRD_CC' => 'Wirecard',
    'SHOPGATE_PAYMENT_METHOD_WLDPDIR_CC' => 'WorldPay (Offsite)',
    'SHOPGATE_PAYMENT_METHOD_WLDPOFF_CC' => 'WorldPay (Offsite)',

    'SHOPGATE_PAYMENT_METHOD_INVOICE'    => 'Own settlement',
    'SHOPGATE_PAYMENT_METHOD_KLARNA_INV' => 'Klarna',
    'SHOPGATE_PAYMENT_METHOD_BILLSAFE'   => 'Billsafe',
    'SHOPGATE_PAYMENT_METHOD_MSTPAY_INV' => 'Masterpayment',
    'SHOPGATE_PAYMENT_METHOD_PAYMRW_INV' => 'Paymorrow',
    'SHOPGATE_PAYMENT_METHOD_PAYONE_INV' => 'PAYONE',
    'SHOPGATE_PAYMENT_METHOD_PAYOL_INV'  => 'Payolution',

    'SHOPGATE_PAYMENT_METHOD_PAYPAL'     => 'PayPal',
    'SHOPGATE_PAYMENT_METHOD_CMPTOP_PP'  => 'Computop PayPal',
    'SHOPGATE_PAYMENT_METHOD_MASTPAY_PP' => 'Masterpayment',
    'SHOPGATE_PAYMENT_METHOD_SAGEPAY_PP' => 'SagePay',

    'SHOPGATE_PAYMENT_METHOD_MERCH_PM'   => 'Merchant Payment #1',
    'SHOPGATE_PAYMENT_METHOD_MERCH_PM_2' => 'Merchant Payment #2',
    'SHOPGATE_PAYMENT_METHOD_MERCH_PM_3' => 'Merchant Payment #3',

    'SHOPGATE_PAYMENT_METHOD_CNB' => 'Click&Buy',

    'SHOPGATE_PAYMENT_METHOD_MWS' => 'Amazon Payment',

    'SHOPGATE_PAYMENT_METHOD_PAYU' => 'PayU',

    'SHOPGATE_PAYMENT_METHOD_SUE' => 'Sofortueberweisung',
);
