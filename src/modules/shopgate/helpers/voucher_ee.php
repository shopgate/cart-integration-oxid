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
 * Class ShopgateVoucherHelperEE
 */
class ShopgateVoucherHelperEE extends ShopgateVoucherHelper
{
    /**
     * @return oxVoucherSerie
     */
    public function createVoucherSeriesForShopgate()
    {
        $this->log("Create voucher series for oxidEE", ShopgateLogger::LOGTYPE_DEBUG);

        $shopId   = marm_shopgate::getOxConfig()->getShopId();
        $shopIncl = pow(2, $shopId - 1);

        $oxVoucherSeries = parent::createVoucherSeriesForShopgate();

        $oxVoucherSeries->oxvoucherseries__oxshopincl = new oxField($shopIncl, oxField::T_RAW);
        $oxVoucherSeries->oxvoucherseries__oxshopexcl = new oxField(0, oxField::T_RAW);
        $oxVoucherSeries->save();

        return $oxVoucherSeries;
    }
}
