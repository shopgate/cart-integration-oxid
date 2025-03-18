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

namespace Shopgate\Oxid\Controller\Admin;

use OxidEsales\Eshop\Core\Field;
use Shopgate\Oxid\Core\Module;
use Shopgate\Oxid\Model\ShopgateOrder;

/**
 * Admin controller for Shopgate config tab
 */
class Order extends Order_Parent
{
    /**
     * shopgate configuration template
     *
     * @var string
     */
    protected $_sThisTemplate = 'shopgate_order.tpl';

    /**
     * stores array for shopgate config, with information how to display it
     *
     * @var array
     */
    protected $_aShopgateConfig = null;

    protected $isError = false;

    protected $errorMessage = '';

    public function syncorder()
    {
        $orderId = Module::getRequestParameter('oxid');
        /** @var ShopgateOrder $order */
        $order = oxNew(ShopgateOrder::class);

        if ($order->load($orderId, 'oxorderid')) {
            if (!$order->syncFromShopgate()) {
                $this->isError = true;
                $this->errorMessage = 'Error on sync order with Shopgate';
            }
        }
    }

    public function link_order()
    {
        $shopgateOrderNumber = Module::getRequestParameter('shopgate_order_number');
        if (!$shopgateOrderNumber) {
            $this->isError = true;
            $this->errorMessage = "Missing Shopgate order number";

            return;
        }

        $orderId = Module::getRequestParameter('oxid');

        /** @var ShopgateOrder $order */
        $order = oxNew(ShopgateOrder::class);

        if ($order->load($shopgateOrderNumber, "order_number")) {
            $this->isError = true;
            $this->errorMessage = "Order already exists!";

            if (($oxOrder = $order->getOxidOrder())) {
                $this->errorMessage .= " Order number: {$oxOrder->oxorder__oxordernr->value}";
            }

            return;
        }

        $order->oxordershopgate__order_number = new Field($shopgateOrderNumber, Field::T_RAW);
        $order->oxordershopgate__oxorderid = new Field($orderId, Field::T_RAW);
        $order->save();

        $this->syncorder();
    }

    public function unlink_order()
    {
        $orderId = Module::getRequestParameter('oxid');

        /** @var ShopgateOrder $order */
        $order = oxNew(ShopgateOrder::class);
        $order->load($orderId, 'oxorderid');
        $order->delete();
    }

    public function reset()
    {
        $orderId = Module::getRequestParameter('oxid');

        /** @var ShopgateOrder $order */
        $order = oxNew(ShopgateOrder::class);
        if ($order->load($orderId, 'oxorderid')) {
            $order->oxordershopgate__is_sent_to_shopgate = new Field("0", Field::T_RAW);
            $order->save();
        }
    }

    public function getShopgateOrder($blReset = false)
    {
        $id = $this->getEditObjectId();

        /** @var ShopgateOrder $order */
        $order = oxNew(ShopgateOrder::class);

        if ($order->load($id, 'oxorderid') && !$order->oxordershopgate__order_data->value) {
            $this->syncorder();
        }

        return $order;
    }

    public function getIsError()
    {
        return $this->isError;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function getIsShopgateOrder()
    {
        $id = $this->getEditObjectId();
        /** @var ShopgateOrder $order */
        $order = oxNew(ShopgateOrder::class);

        return $order->load($id, 'oxorderid');
    }
}
