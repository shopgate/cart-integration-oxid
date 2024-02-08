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
 * Class ShopgateUserHelperEE
 */
class ShopgateUserHelperEE extends ShopgateUserHelper
{
    /**
     * @param oxUser                $oxUser
     * @param ShopgateCartBase|null $shopgateOrder
     *
     * @return oxUser
     */
    public function setOxidUserInvoiceData(oxUser $oxUser, ShopgateCartBase $shopgateOrder = null)
    {
        $this->log("Call Function 'setOxidUserInvoiceData' for OxidEE", ShopgateLogger::LOGTYPE_DEBUG);

        $oxUser = parent::setOxidUserInvoiceData($oxUser, $shopgateOrder);

        // ATTENTION omitting empty fields (oxustidstatus) leads to a fatal error
        $oxUser->oxuser__oxustidstatus = new oxField("", oxField::T_RAW);

        return $oxUser;
    }
}
