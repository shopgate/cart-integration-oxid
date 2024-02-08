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

class oxOrderShopgate extends oxBase
{
    public function __construct()
    {
        parent::__construct();
        $this->init('oxordershopgate');
    }

    public function save()
    {
        if (!empty($this->oxordershopgate__order_data->value)) {
            marm_shopgate::getInstance()->init();
            $object = unserialize(base64_decode($this->oxordershopgate__order_data->value));

            if (is_object($object)) {
                $this->oxordershopgate__is_paid             = new oxField($object->getIsPaid(), oxField::T_RAW);
                $this->oxordershopgate__is_shipping_blocked = new oxField(
                    $object->getIsShippingBlocked(),
                    oxField::T_RAW
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
            $sOXID = oxDb::getDb()->GetOne("SELECT oxid FROM {$this->getViewName()} WHERE {$type} = '$sOXID'");
        }

        return parent::load($sOXID);
    }

    public function getOrderData()
    {
        $order = null;
        if ($this->oxordershopgate__order_data->value) {
            marm_shopgate::getInstance()->init();
            $data  = $this->oxordershopgate__order_data->value;
            $data  = base64_decode($data);
            $order = unserialize($data);
        }

        return $order;
    }

    /**
     *
     * @return ShopgateOrder|null
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
     * @return oxOrder
     */
    public function getOxidOrder()
    {
        /** @var oxOrder $oxOrder */
        $oxOrder = oxnew("oxorder");
        $oxOrder->load($this->oxordershopgate__oxorderid->value);

        return $oxOrder;
    }

    public function syncFromShopgate()
    {
        if (!$this->isLoaded()) {
            return false;
        }

        try {
            marm_shopgate::getInstance()->init();

            /** @var ShopgateBuilder $builder */
            $builder = oxNew("ShopgateBuilder", marm_shopgate::getInstance()->getConfig());

            /** @var ShopgateMerchantApi $oShopgateMerchantApi */
            $oShopgateMerchantApi              = $builder->buildMerchantApi();
            $parameters                        = array(
                'order_numbers[0]' => $this->oxordershopgate__order_number->value,
                'with_items'       => 1,
            );
            $orders                            = $oShopgateMerchantApi->getOrders($parameters)->getData();
            $_order                            = array_shift($orders);
            $this->oxordershopgate__order_data = new oxField(base64_encode(serialize($_order)), oxField::T_RAW);
            $this->save();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function cancelOrder()
    {
        $sma = marm_shopgate::getInstance()->getShopgateMerchantApiInstance();

        /** @var oxOrder $oOxOrder */
        $oOxOrder = $this->getOxidOrder();

        $sOrderNumber         = $this->oxordershopgate__order_number->value;
        $bCancelCompleteOrder = false;
        $aCancellationItems   = array();
        $bCancelShipping      = false;
        $sCancellationNote    = "Order canceled in Oxid!";

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
            $totalItemQty      = 0;
            $totalItemCanceled = 0;

            $aItemStack     = array();
            $oShopgateOrder = $this->getShopgateOrder();
            foreach ($oShopgateOrder->getItems() as $item) {
                $infos                              = $item->jsonDecode($item->getInternalOrderInfo(), true);
                $aItemStack[$infos["article_oxid"]] = $item;
            }

            foreach ($oOxOrder->getOrderArticles(false) as $oOxOrderArticle) {
                /** @var oxOrderArticle $oOxOrderArticle */
                /** @var ShopgateOrderItem $item */

                // we need the item_number from ShopgateOrderItem because in export
                // the item_number can be oxid order article number
                $item = $aItemStack[$oOxOrderArticle->oxorderarticles__oxartid->value];
                if (!$item) {
                    continue;
                }
                $sItemNumber = $item->getItemNumber();
                $iQuantity   = $oOxOrderArticle->oxorderarticles__oxamount->value;

                $totalItemQty += $oOxOrderArticle->oxorderarticles__oxamount->value;

                if ($oOxOrderArticle->oxorderarticles__oxstorno->value && !isset($aOldCancellationItems[$sItemNumber])) {
                    $totalItemCanceled += $oOxOrderArticle->oxorderarticles__oxamount->value;

                    $aCancellationItems[$sItemNumber] = array(
                        "item_number" => $sItemNumber,
                        "quantity"    => $iQuantity,
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
                $this->oxordershopgate__is_cancellation_sent_to_shopgate = new oxField("1", oxField::T_RAW);
            }
            $aCancellationItems                            = $aCancellationItems + $aOldCancellationItems;
            $aCancellationItems                            = base64_encode(serialize($aCancellationItems));
            $this->oxordershopgate__reported_cancellations = new oxField($aCancellationItems, oxField::T_RAW);

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
     * @throws ShopgateMerchantApiException
     * @return boolean
     */
    public function confirmShipping()
    {
        try {
            $sma = marm_shopgate::getInstance()->getShopgateMerchantApiInstance();

            /** @var oxorder $oOxidOrder */
            $oOxidOrder = $this->getOxidOrder();

            $sShogateOrderNumber = $this->oxordershopgate__order_number->value;
            $sDeliveryService    = ShopgateDeliveryNote::OTHER;
            $sTrackingCode       = $oOxidOrder->oxorder__oxtrackcode->value;

            if ($sTrackingCode) {
                try {
                    $sma->addOrderDeliveryNote($sShogateOrderNumber, $sDeliveryService, $sTrackingCode);
                } catch (Exception $e) {
                    /* error on addDeliveryNote is not important! */
                }
            }

            $sma->setOrderShippingCompleted($sShogateOrderNumber);
            $this->oxordershopgate__is_sent_to_shopgate = new oxField("1", oxField::T_RAW);
            $this->save();
        } catch (ShopgateMerchantApiException $e) {
            if ($e->getCode() == ShopgateMerchantApiException::ORDER_SHIPPING_STATUS_ALREADY_COMPLETED || $e->getCode(
                ) == ShopgateMerchantApiException::ORDER_ALREADY_COMPLETED) {
                $this->oxordershopgate__is_sent_to_shopgate = new oxField("1", oxField::T_RAW);
                $this->save();
            }
            throw $e;
        }

        return true;
    }
}
