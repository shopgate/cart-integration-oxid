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

namespace Shopgate\Oxid\Model;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\OrderArticle;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Model\BaseModel;
use OxidEsales\Eshop\Core\Field;
use Shopgate\Oxid\Core\Module;
use ShopgateBuilder;
use ShopgateDeliveryNote;
use ShopgateLogger;
use ShopgateMerchantApi;
use ShopgateMerchantApiException;
use ShopgateOrder as ShopgatePluginApiOrder;
use ShopgateOrderItem as ShopgatePluginApiOrderItem;

class ShopgateOrder extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->init('oxordershopgate');
    }

    public function save()
    {
        if (!empty($this->oxordershopgate__order_data->value)) {
            Module::getInstance()->init();
            $object = unserialize(base64_decode($this->oxordershopgate__order_data->value));

            if (is_object($object)) {
                $this->oxordershopgate__is_paid = new Field($object->getIsPaid(), Field::T_RAW);
                $this->oxordershopgate__is_shipping_blocked = new Field(
                    $object->getIsShippingBlocked(),
                    Field::T_RAW
                );
            } else {
                ShopgateLogger::getInstance()->log(
                    __FUNCTION__ . ': order_data is not a valid object: ' . var_export($object, true),
                    ShopgateLogger::LOGTYPE_ERROR
                );
            }
        }

        return parent::save();
    }

    public function load($sOXID, $type = "oxid")
    {
        if ($type != "oxid") {
            // TODO DatabaseProvider is deprecated since Oxid 6.4, change to using QueryBuilder at some point
            $sOXID = DatabaseProvider::getDb()->GetOne("SELECT oxid FROM {$this->getViewName()} WHERE {$type} = '$sOXID'");
        }

        return parent::load($sOXID);
    }

    public function getOrderData()
    {
        $order = null;
        if ($this->oxordershopgate__order_data->value) {
            Module::getInstance()->init();
            $data = $this->oxordershopgate__order_data->value;
            $data = base64_decode($data);
            $order = unserialize($data);
        }

        return $order;
    }

    /**
     *
     * @return ShopgatePluginApiOrder|null
     */
    public function getShopgateOrder()
    {
        $order = $this->getOrderData();
        if (!$order) {
            if ($this->syncFromShopgate()) {
                $order = $this->getOrderData();
            }
        }

        return $order;
    }

    /**
     * @return Order
     */
    public function getOxidOrder()
    {
        /** @var Order $oxOrder */
        $oxOrder = oxnew(Order::class);
        $oxOrder->load($this->oxordershopgate__oxorderid->value);

        return $oxOrder;
    }

    public function syncFromShopgate()
    {
        if (!$this->isLoaded()) {
            return false;
        }

        try {
            Module::getInstance()->init();

            /** @var ShopgateBuilder $builder */
            $builder = oxNew("ShopgateBuilder", Module::getInstance()->getConfig());

            /** @var ShopgateMerchantApi $oShopgateMerchantApi */
            $oShopgateMerchantApi = $builder->buildMerchantApi();
            $parameters = array(
                'order_numbers[0]' => $this->oxordershopgate__order_number->value,
                'with_items' => 1,
            );
            $orders = $oShopgateMerchantApi->getOrders($parameters)->getData();
            $_order = array_shift($orders);
            $this->oxordershopgate__order_data = new Field(base64_encode(serialize($_order)), Field::T_RAW);
            $this->save();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function cancelOrder()
    {
        $sma = Module::getInstance()->getShopgateMerchantApiInstance();

        /** @var Order $oOxOrder */
        $oOxOrder = $this->getOxidOrder();

        $sOrderNumber = $this->oxordershopgate__order_number->value;
        $bCancelCompleteOrder = false;
        $aCancellationItems = array();
        $bCancelShipping = false;
        $sCancellationNote = "Order canceled in Oxid!";

        $aOldCancellationItems = $this->oxordershopgate__reported_cancellations->value;

        $aOldCancellationItems = unserialize(base64_decode($aOldCancellationItems));
        if (!$aOldCancellationItems) {
            $aOldCancellationItems = array();
        }

        if ($oOxOrder->oxorder__oxstorno->value) {
            // On full cancellation make the request and return
            $bCancelCompleteOrder = true;
        } else {

            // partial cancellation...
            $totalItemQty = 0;
            $totalItemCanceled = 0;

            $aItemStack = array();
            $oShopgateOrder = $this->getShopgateOrder();
            foreach ($oShopgateOrder->getItems() as $item) {
                $infos = $item->jsonDecode($item->getInternalOrderInfo(), true);
                $aItemStack[$infos["article_oxid"]] = $item;
            }

            foreach ($oOxOrder->getOrderArticles(false) as $oOxOrderArticle) {
                /** @var OrderArticle $oOxOrderArticle */
                /** @var ShopgatePluginApiOrderItem $item */

                // we need the item_number from ShopgateOrderItem because in export
                // the item_number can be oxid order article number
                $item = $aItemStack[$oOxOrderArticle->oxorderarticles__oxartid->value];
                if (!$item) {
                    continue;
                }
                $sItemNumber = $item->getItemNumber();
                $iQuantity = $oOxOrderArticle->oxorderarticles__oxamount->value;

                $totalItemQty += $oOxOrderArticle->oxorderarticles__oxamount->value;

                if ($oOxOrderArticle->oxorderarticles__oxstorno->value && !isset($aOldCancellationItems[$sItemNumber])) {
                    $totalItemCanceled += $oOxOrderArticle->oxorderarticles__oxamount->value;

                    $aCancellationItems[$sItemNumber] = array(
                        "item_number" => $sItemNumber,
                        "quantity" => $iQuantity,
                    );
                } else {
                    if (isset($aOldCancellationItems[$sItemNumber])) {
                        $totalItemCanceled += $aOldCancellationItems[$sItemNumber]["quantity"];
                    }
                }
            }

            if ($totalItemCanceled >= $totalItemQty) {
                $bCancelCompleteOrder = true;
            }
        }

        if ($bCancelCompleteOrder || $aCancellationItems || $bCancelShipping) {
            try {
                $sma->cancelOrder(
                    $sOrderNumber,
                    $bCancelCompleteOrder,
                    $aCancellationItems,
                    $bCancelShipping,
                    $sCancellationNote
                );
            } catch (ShopgateMerchantApiException $exception) {
                // 222 means "order already canceled" which isn't actually a problem...
                if ($exception->getCode() != 222) {
                    throw $exception;
                }
            }

            if ($bCancelCompleteOrder) {
                $this->oxordershopgate__is_cancellation_sent_to_shopgate = new Field("1", Field::T_RAW);
            }
            $aCancellationItems = $aCancellationItems + $aOldCancellationItems;
            $aCancellationItems = base64_encode(serialize($aCancellationItems));
            $this->oxordershopgate__reported_cancellations = new Field($aCancellationItems, Field::T_RAW);

            $this->save();
            if (!empty($exception)) {
                throw $exception;
            }
        }

        return true;
    }

    /**
     * Set shipping completed to shopgate
     *
     * @return boolean
     * @throws ShopgateMerchantApiException
     */
    public function confirmShipping()
    {
        try {
            $sma = Module::getInstance()->getShopgateMerchantApiInstance();

            /** @var Order $oOxidOrder */
            $oOxidOrder = $this->getOxidOrder();

            $sShogateOrderNumber = $this->oxordershopgate__order_number->value;
            $sDeliveryService = ShopgateDeliveryNote::OTHER;
            $sTrackingCode = $oOxidOrder->oxorder__oxtrackcode->value;

            if ($sTrackingCode) {
                try {
                    $sma->addOrderDeliveryNote($sShogateOrderNumber, $sDeliveryService, $sTrackingCode);
                } catch (Exception $e) {
                    /* error on addDeliveryNote is not important! */
                }
            }

            $sma->setOrderShippingCompleted($sShogateOrderNumber);
            $this->oxordershopgate__is_sent_to_shopgate = new Field("1", Field::T_RAW);
            $this->save();
        } catch (ShopgateMerchantApiException $e) {
            if (
                $e->getCode() == ShopgateMerchantApiException::ORDER_SHIPPING_STATUS_ALREADY_COMPLETED ||
                $e->getCode() == ShopgateMerchantApiException::ORDER_ALREADY_COMPLETED
            ) {
                $this->oxordershopgate__is_sent_to_shopgate = new Field("1", Field::T_RAW);
                $this->save();
            }
            throw $e;
        }

        return true;
    }
}
