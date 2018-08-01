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

if (!defined('SHOPGATE_PLUGIN_VERSION')) {
    define("SHOPGATE_PLUGIN_VERSION", "2.9.77");
}

/**
 * Metadata version
 */
$sMetadataVersion = '1.1';

/**
 * Module information
 *
 * @see http://wiki.oxidforge.org/Features/Extension_metadata_file
 */
$aModule = array(
    'id'          => 'shopgate',
    'title'       => 'Shopgate',
    'description' => 'Mobile Shopping with Shopgate for OXID',
    'thumbnail'   => 'picture.jpg',
    'version'     => SHOPGATE_PLUGIN_VERSION,
    'author'      => 'Shopgate GmbH',
    'email'       => 'technik@shopgate.com',
    'url'         => 'http://www.shopgate.com',
    'extend'      => array(
        'oxorder'          => 'shopgate/marm_shopgate_oxorder',
        'oxoutput'         => 'shopgate/marm_shopgate_oxoutput',
        'oxarticle'        => 'shopgate/marm_shopgate_oxarticle',
        'order_overview'   => 'shopgate/shopgate_order_overview',
        'order_main'       => 'shopgate/shopgate_order_overview',
        'oxvarianthandler' => 'shopgate/shopgate_oxvarianthandler',
        'oxvoucher'        => 'shopgate/shopgate_oxvoucher',
        'oxbasket'         => 'shopgate/shopgate_oxbasket',
        'oxdeliverylist'   => 'shopgate/shopgate_oxdeliverylist',
        'oxsession'        => 'shopgate/shopgate_oxsession',
    ),
    'files'       => array(
        'marm_shopgate'   => 'shopgate/core/marm_shopgate.php',
        'oxOrderShopgate' => 'shopgate/core/oxordershopgate.php',

        'marm_shopgate_article' => 'shopgate/admin/marm_shopgate_article.php',
        'marm_shopgate_config'  => 'shopgate/admin/marm_shopgate_config.php',

        'shopgate_order'    => 'shopgate/admin/shopgate_order.php',
        'shopgate_shipping' => 'shopgate/admin/shopgate_shipping.php',
        'shopgate_payment'  => 'shopgate/admin/shopgate_payment.php',
        'shopgate_actions'  => 'shopgate/admin/shopgate_actions.php',

        'marm_shopgate_api' => 'shopgate/views/marm_shopgate_api.php',
    ),
    'templates'   => array(
        'marm_shopgate_article.tpl' => 'shopgate/out/admin/tpl/marm_shopgate_article.tpl',
        'marm_shopgate_config.tpl'  => 'shopgate/out/admin/tpl/marm_shopgate_config.tpl',
        'shopgate_order.tpl'        => 'shopgate/out/admin/tpl/shopgate_order.tpl',
        'shopgate_shipping.tpl'     => 'shopgate/out/admin/tpl/shopgate_shipping.tpl',
        'shopgate_payment.tpl'      => 'shopgate/out/admin/tpl/shopgate_payment.tpl',
        'shopgate_actions.tpl'      => 'shopgate/out/admin/tpl/shopgate_actions.tpl',
    ),
    'events'      => array(
        'onActivate'   => 'marm_shopgate::onActivate',
        'onDeactivate' => 'marm_shopgate::onDeactivate',
    ),
);
