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
    define("SHOPGATE_PLUGIN_VERSION", "2.10.2");
}

/**
 * Metadata version
 */
$sMetadataVersion = '2.1';

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
        // not using Oxid _parent structure:
        // OxidEsales\Eshop\Application\Controller\FrontendController::class => Shopgate\Oxid\Controller\ShopgatePluginApi::class,

        OxidEsales\Eshop\Application\Model\Article::class => Shopgate\Oxid\Model\Article::class,
        OxidEsales\Eshop\Application\Model\Basket::class => Shopgate\Oxid\Model\Basket::class,
        OxidEsales\Eshop\Application\Model\DeliveryList::class => Shopgate\Oxid\Model\DeliveryList::class,
        OxidEsales\Eshop\Application\Model\Order::class => Shopgate\Oxid\Model\Order::class,
        OxidEsales\Eshop\Application\Model\VariantHandler::class => Shopgate\Oxid\Model\VariantHandler::class,
        OxidEsales\Eshop\Application\Model\Voucher::class => Shopgate\Oxid\Model\Voucher::class,

        OxidEsales\Eshop\Application\Controller\Admin\ActionsController::class => Shopgate\Oxid\Controller\Admin\Actions::class,
        OxidEsales\Eshop\Application\Controller\Admin\ArticleController::class => Shopgate\Oxid\Controller\Admin\Article::class,
        OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration::class => Shopgate\Oxid\Controller\Admin\Config::class,
        OxidEsales\Eshop\Application\Controller\Admin\OrderMain::class => Shopgate\Oxid\Controller\Admin\Order::class,
        OxidEsales\Eshop\Application\Controller\Admin\OrderOverview::class => Shopgate\Oxid\Controller\Admin\OrderOverview::class,
        OxidEsales\Eshop\Application\Controller\Admin\PaymentMain::class => Shopgate\Oxid\Controller\Admin\Payment::class,
        OxidEsales\Eshop\Application\Controller\Admin\DeliverySetMain::class => Shopgate\Oxid\Controller\Admin\Shipping::class,

        OxidEsales\Eshop\Core\Output::class => Shopgate\Oxid\Core\Output::class,
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
