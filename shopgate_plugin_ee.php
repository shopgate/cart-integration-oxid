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

if (!function_exists('getShopBasePath')) {
    $sOxidConfigDir = '../..';
    function getShopBasePath()
    {
        return dirname(__FILE__) . '/../../';
    }

    require_once(getShopBasePath() . '/core/oxfunctions.php');
    require_once(getShopBasePath() . "/core/adodblite/adodb.inc.php");
}

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/metadata.php';
require_once dirname(__FILE__) . '/shopgate_plugin.php';
require_once dirname(__FILE__) . '/helpers/voucher_ee.php';
require_once dirname(__FILE__) . '/helpers/user_ee.php';

class ShopgatePluginOxidEE extends ShopgatePluginOxid
{
    public function startup()
    {
        parent::startup();

        $this->userHelper    = new ShopgateUserHelperEE($this->config);
        $this->voucherHelper = new ShopgateVoucherHelperEE();
        $this->basketHelper  = new ShopgateBasketHelper(
            $this->userHelper,
            $this->voucherHelper,
            $this->shippingHelper
        );
    }
}
