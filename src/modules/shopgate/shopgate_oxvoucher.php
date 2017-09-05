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

class shopgate_oxvoucher extends shopgate_oxvoucher_parent
{
    /**
     * Override the oxVoucher::getDiscountValue
     *
     * On shopgate API request and shopgate coupon return the voucher
     * discount and not the series discount
     *
     * @param double $dPrice
     *
     * @return double
     */
    public function getDiscountValue($dPrice)
    {
        if (
            defined("_SHOPGATE_API") && _SHOPGATE_API
            && $this->getSerie()->getId() == ShopgateVoucherHelper::VOUCHER_OXID
        ) {
            $dVoucher = doubleval($this->oxvouchers__oxdiscount->value);

            if ($dVoucher > $dPrice) {
                return $dPrice;
            }

            return $dVoucher;
        }

        return parent::getDiscountValue($dPrice);
    }

    /**
     * The return annotation is faulty in the original Oxid method, so we override it here...
     *
     * @return oxVoucherSerie
     */
    public function getSerie()
    {
        return parent::getSerie();
    }
}
