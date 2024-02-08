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

class marm_shopgate_oxorder extends marm_shopgate_oxorder_parent
{
    /**
     * Usually defined in parent class, but missing in Oxid 4.1
     */
    const ORDER_STATE_MAILINGERROR = 0;
    /**
     * Usually defined in parent class, but missing in Oxid 4.1
     */
    const ORDER_STATE_OK = 1;

    /**
     * Delete the entry for shopgate order data if oxorder will delete
     *
     * @param string $sOXID
     *
     * @return boolean
     * @see oxOrder::delete
     */
    public function delete($sOXID = null)
    {
        parent::delete($sOXID);

        # you can't rely on the parent function returning true or false
        # cause some other modules override the function and don't return anything

        $oxOrderId = $sOXID
            ? $sOXID
            : $this->getId();
        if (!$this->load($oxOrderId)) {
            /** @var oxOrderShopgate $shopgateOrder */
            $shopgateOrder = oxNew("oxOrderShopgate");
            if ($shopgateOrder->load($oxOrderId, "oxorderid")) {
                $shopgateOrder->delete();
            }

            return true;
        }

        return false;
    }

    /**
     * executes parent save, calls marmCheckSendDate, returns parent result
     *
     * @return boolean
     * @see oxOrder::save
     */
    public function save()
    {
        $blResult = parent::save();
        $this->_marmCheckSendDate();

        return $blResult;
    }

    /**
     * if order send date is set, and order is from shopgate,
     *
     * @return void
     */
    protected function _marmCheckSendDate()
    {
        $iTime = strtotime($this->oxorder__oxsenddate->value);

        /** @var oxOrderShopgate $shopgateOrder */
        $shopgateOrder = oxNew("oxOrderShopgate");

        if ($iTime > 0 && $shopgateOrder->load($this->getId(), "oxorderid")) {
            try {
                $shopgateOrder->confirmShipping();
            } catch (Exception $e) {
            }
        }
    }

    /**
     * ignore basket and order validation
     *
     * @param oxbasket $oBasket
     * @param oxuser   $oUser
     *
     * @return NULL|int
     * @see oxOrder::validateOrder
     */
    public function validateOrder($oBasket, $oUser)
    {
        if (defined("_SHOPGATE_API") && _SHOPGATE_API) {
            return null;
        }

        return parent::validateOrder($oBasket, $oUser);
    }

    /**
     * @param oxUser        $oUser
     * @param oxBasket      $oBasket
     * @param oxUserPayment $oPayment
     *
     * @return number
     * @see oxOrder::_sendOrderByEmail
     */
    protected function _sendOrderByEmail($oUser = null, $oBasket = null, $oPayment = null)
    {
        $sendToUser  = marm_shopgate::getInstance()->getConfig()->getSendMails();
        $sendToOwner = marm_shopgate::getInstance()->getConfig()->getSendMailsToOwner();
        if (!defined("_SHOPGATE_API") || !_SHOPGATE_API || ($sendToUser && $sendToOwner)) {
            return parent::_sendOrderByEmail($oUser, $oBasket, $oPayment);
        }

        $this->_oUser    = $oUser;
        $this->_oBasket  = $oBasket;
        $this->_oPayment = $oPayment;

        /** @var oxEmail $oxEmail */
        $oxEmail = oxNew('oxEmail');

        if ($sendToOwner) {
            $oxEmail->sendOrderEMailToOwner($this);
        }

        if ($sendToUser && !$oxEmail->sendOrderEMailToUser($this)) {
            return self::ORDER_STATE_MAILINGERROR;
        }

        return self::ORDER_STATE_OK;
    }

    protected function _executePayment(oxBasket $oBasket, $oUserpayment)
    {
        if (defined("_SHOPGATE_API") && _SHOPGATE_API && defined(
                "_SHOPGATE_ACTION"
            ) && _SHOPGATE_ACTION == 'add_order') {
            return true;
        }

        return parent::_executePayment($oBasket, $oUserpayment);
    }
}
