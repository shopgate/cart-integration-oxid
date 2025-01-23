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
    function getShopBasePath()
    {
        return dirname(__FILE__) . '/../../../';
    }
}

if (file_exists(getShopBasePath() . "bootstrap.php")) {
    include_once getShopBasePath() . "bootstrap.php";
}

/**
 * Frontend controller for handling Shopgate integration requests
 */
class marm_shopgate_api extends oxUBase
{
    /**
     * For performance.
     * no parent::init call, so no components and other objects created
     *
     * @return void
     */
    public function init()
    {
        if (isAdmin()) {
            die('ERROR: API URL cannot be Admin URL');
        }
        define("_SHOPGATE_API", true);
        define("_SHOPGATE_ACTION", $_REQUEST["action"]);
    }

    /**
     * Loads framework, and executes start action in it.
     * After this, oxid will exit without template rendering ( oxUtils::showMessageAndExit())
     *
     * @return void
     */
    public function render()
    {
        $oShopgateFramework = marm_shopgate::getInstance()->getFramework();
        $oShopgateFramework->handleRequest($_REQUEST);

        marm_shopgate::getOxUtils()->showMessageAndExit('');
    }
}
