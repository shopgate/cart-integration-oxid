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

class shopgate_order_overview extends shopgate_order_overview_parent
{
    /**
     * @see Order_Overview::render()
     */
    public function render()
    {
        $value = parent::render();
        /** @var oxOrderShopgate $shopgateOrder */
        $shopgateOrder = oxNew("oxordershopgate");

        // If this is a shopgate order ...
        if ($shopgateOrder->load(marm_shopgate::getRequestParameter("oxid"), "oxorderid")) {
            $oxidOrder = $shopgateOrder->getOxidOrder();

            // ... and the is not canceled ...
            if (!$oxidOrder->oxorder__oxstorno->value) {
                // ... and the shipping is blocked by shopgate ...
                if ($shopgateOrder->oxordershopgate__is_shipping_blocked->value) {
                    // ... set the buttons to readonly
                    $this->_aViewData["readonly"] = "readonly";
                }
            }

            if (!isset($this->_aViewData["oPayments"]) || !$this->_aViewData["oPayments"]) {
                $this->_aViewData["oPayments"] = array();
            }

            /** @var oxPayment $p */
            $p = oxnew("oxpayment");
            $p->load("oxshopgate");
            $this->_aViewData["oPayments"][$p->getId()] = $p;

            $p = oxnew("oxpayment");
            $p->load("oxmobile_payment");
            $this->_aViewData["oPayments"][$p->getId()] = $p;
        }

        return $value;
    }
}
