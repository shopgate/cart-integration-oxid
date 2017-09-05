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

/**
 * Admin controller for Shopgate config tab
 */
class shopgate_order extends oxAdminDetails
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
        $orderId = marm_shopgate::getRequestParameter('oxid');
        /** @var oxOrderShopgate $order */
        $order = oxNew('oxordershopgate');

        if ($order->load($orderId, 'oxorderid')) {
            if (!$order->syncFromShopgate()) {
                $this->isError      = true;
                $this->errorMessage = 'Error on sync order with Shopgate';
            }
        }
    }

    public function link_order()
    {
        $shopgateOrderNumber = marm_shopgate::getRequestParameter('shopgate_order_number');
        if (!$shopgateOrderNumber) {
            $this->isError      = true;
            $this->errorMessage = "Missing Shopgate order number";

            return;
        }

        $orderId = marm_shopgate::getRequestParameter('oxid');

        /** @var oxOrderShopgate $order */
        $order = oxNew('oxordershopgate');

        if ($order->load($shopgateOrderNumber, "order_number")) {
            $this->isError      = true;
            $this->errorMessage = "Order already exists!";

            if (($oxOrder = $order->getOxidOrder())) {
                $this->errorMessage .= " Order number: {$oxOrder->oxorder__oxordernr->value}";
            }

            return;
        }

        $order->oxordershopgate__order_number = new oxField($shopgateOrderNumber, oxField::T_RAW);
        $order->oxordershopgate__oxorderid    = new oxField($orderId, oxField::T_RAW);
        $order->save();

        $this->syncorder();
    }

    public function unlink_order()
    {
        $orderId = marm_shopgate::getRequestParameter('oxid');

        /** @var oxOrderShopgate $order */
        $order = oxNew('oxordershopgate');
        $order->load($orderId, 'oxorderid');
        $order->delete();
    }

    public function reset()
    {
        $orderId = marm_shopgate::getRequestParameter('oxid');

        /** @var oxOrderShopgate $order */
        $order = oxNew('oxordershopgate');
        if ($order->load($orderId, 'oxorderid')) {
            $order->oxordershopgate__is_sent_to_shopgate = new oxField("0", oxField::T_RAW);
            $order->save();
        }
    }

    public function getShopgateOrder($blReset = false)
    {
        $id = $this->getEditObjectId();

        /** @var oxOrderShopgate $order */
        $order = oxNew('oxordershopgate');

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
        /** @var oxOrderShopgate $order */
        $order = oxNew('oxordershopgate');

        return $order->load($id, 'oxorderid');
    }
}
