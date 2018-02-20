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
 * Class ShopgateVoucherHelper
 */
class ShopgateVoucherHelper extends ShopgateObject
{
    const VOUCHER_OXID = 'shopgate_voucher';

    /**
     * @param oxVoucher $oxVoucher
     *
     * @return bool
     */
    public function isVoucherAlreadyUsed(oxVoucher $oxVoucher)
    {
        if (_SHOPGATE_ACTION == ShopgatePluginOxid::ACTION_ADD_ORDER) {
            // In add_order we have no choice but to use the given coupon, even if it has already been used.
            // Otherwise we might not be able to import the order.
            return false;
        }
        $dateUsed = (int)str_replace('-', '', $oxVoucher->oxvouchers__oxdateused->value);

        return !empty($dateUsed);
    }

    /**
     * @return oxVoucherSerie
     *
     * @throws ShopgateLibraryException
     */
    public function createVoucherSeriesForShopgate()
    {
        $this->log("Create voucher series", ShopgateLogger::LOGTYPE_DEBUG);
        $voucherId = self::VOUCHER_OXID;

        /** @var oxVoucherSerie $oxVoucherSeries */
        $oxVoucherSeries = oxNew('oxVoucherSerie');
        $shopId          = marm_shopgate::getOxConfig()->getShopId();

        $qry = "INSERT INTO `{$oxVoucherSeries->getViewName(true)}`
				(`oxid`, `oxshopid`, `oxserienr`, `oxseriedescription`, `oxdiscounttype`)
		 VALUES ('{$voucherId}', '{$shopId}', 'Shopgate Gutscheine', 'Shopgate Gutscheine', 'absolute')";
        marm_shopgate::dbExecute($qry);

        $qry    = "SELECT * FROM `{$oxVoucherSeries->getViewName(true)}` o WHERE o.oxid = '{$voucherId}'";
        $result = marm_shopgate::dbGetAll($qry);

        if (isset($result[0]['OXMAPID'])) {
            // there is an additional table "oxvoucherseries2shop" in newer Oxid versions
            // without an entry there, the series won't be found
            $table = $oxVoucherSeries->getViewName(true) . '2shop';
            $qry   = "INSERT INTO `{$table}`
				(`oxshopid`, `oxmapobjectid`)
		 	 VALUES ('{$shopId}', '{$result[0]['OXMAPID']}')";

            marm_shopgate::dbExecute($qry);
        }

        if (!$oxVoucherSeries->load($voucherId)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                "Cannot create voucher serie",
                true
            );
        }

        $oxVoucherSeries->oxvoucherseries__oxallowsameseries  = new oxField(1, oxField::T_RAW);
        $oxVoucherSeries->oxvoucherseries__oxallowotherseries = new oxField(1, oxField::T_RAW);
        $oxVoucherSeries->oxvoucherseries__oxallowuseanother  = new oxField(1, oxField::T_RAW);
        $oxVoucherSeries->oxvoucherseries__oxminimumvalue     = new oxField(0, oxField::T_RAW);
        $oxVoucherSeries->oxvoucherseries__oxcalculateonce    = new oxField(0, oxField::T_RAW);
        $oxVoucherSeries->save();

        return $oxVoucherSeries;
    }
}
