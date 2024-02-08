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

class shopgate_oxbasket extends shopgate_oxbasket_parent
{
    ###### Variant version rev: 7469; Martin Weber; 14.08.2013 #####
    ###### Variant version #12345; Christian Frenzl; 16.10.2013 BOF #####
    protected function _calcBasketDiscount()
    {
        $result = parent::_calcBasketDiscount();
        if (defined("_SHOPGATE_API") && _SHOPGATE_API) {
            $this->_aDiscounts = array();
            //$this->_aDiscountedVats = array();
        }

        return $result;
    }

    ###### Variant version #12345; Christian Frenzl; 16.10.2013 EOF #####

    public function calculateBasket($blForceUpdate = false)
    {

        /**
         * Workaround for a bug in Oxid < 4.7.1
         * see https://bugs.oxid-esales.com/view.php?id=3982
         */
        $oxidVersion = marm_shopgate::getOxConfig()->getVersion();
        if (version_compare($oxidVersion, '4.7.1', '<')) {
            if ($blForceUpdate) {
                $this->onUpdate();
            }
        }

        return parent::calculateBasket($blForceUpdate);
    }

    protected function _calcDeliveryCost()
    {
        // If delivery costs already set, don't recalculate them (to prevent other plugins from overwriting our delivery costs; see #17908).
        if ($this->_oDeliveryPrice !== null) {
            return $this->_oDeliveryPrice;
        }

        return parent::_calcDeliveryCost();
    }

    public function getDelCostNetAsFloat()
    {
        $cost = $this->getDelCostNet();

        return empty($cost)
            ? $cost
            : floatval(str_replace(',', '.', $cost));
    }

    /**
     * Method didn't exist in Oxid < 4.7
     *
     * @return bool
     */
    public function isCalculationModeNetto()
    {
        if (method_exists('oxBasket', 'isCalculationModeNetto')) {
            /** @noinspection PhpUndefinedMethodInspection */
            return parent::isCalculationModeNetto();
        }

        return false;
    }
}
