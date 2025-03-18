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

namespace Shopgate\Oxid\Core;

use Exception;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use ShopgateLogger;

// todo replace with QueryBuilder

// package-internal autoloader only exists upon manual installation, not on Composer installation
$composerAutoloaderPath = dirname(__FILE__) . '/vendor/autoload.php';
if (file_exists($composerAutoloaderPath)) {
    require_once($composerAutoloaderPath);
}

class Installer
{
    const SHOPGATE_REQUEST_URL = 'https://api.shopgate.com/log';

    public function install($resendUid = false)
    {
        $this->initDB();

        $defaultRedirectConfigKey = Module::getInstance()->getOxidConfigKey('enable_default_redirect');
        Module::getOxConfig()->saveShopConfVar('checkbox', $defaultRedirectConfigKey, false);

        $uid = Module::getOxConfig()->getConfigParam('sg_shop_uid');
        if (empty($uid) || $resendUid) {
            $this->sendData($uid);
        }
    }

    private function initDB()
    {
        $statements = $this->readSqlFile(dirname(__FILE__) . '/install.sql');
        $db         = DatabaseProvider::getDb();
        foreach ($statements as $statement) {
            if (empty($statement)) {
                continue;
            }

            try {
                $db->Execute($statement);
            } catch (Exception $e) {
                ShopgateLogger::getInstance()->log('Error while executing SQL statement: ' . $e->getMessage());
            }
        }
        if (!$this->updateDbViews()) {
            ShopgateLogger::getInstance()->log('DB views not updated.');
        }
    }

    private function updateDbViews()
    {
        if (class_exists('oxDbMetaDataHandler') && method_exists('oxDbMetaDataHandler', 'updateViews')) {
            /** @var DbMetaDataHandler $handler */
            $handler = oxNew(DbMetaDataHandler::class);

            return $handler->updateViews();
        }
        $oxDb = DatabaseProvider::getInstance();
        if (method_exists($oxDb, 'updateViews')) {
            return $oxDb->updateViews();
        }

        return false;
    }

    /**
     * reads SQL file with given name and returns an array of statements
     * statement separator must be ";"
     * comment lines (starting with "--") are ignored
     *
     * @param string $name
     *
     * @return array
     */
    private function readSqlFile($name)
    {
        $sql   = '';
        $lines = file($name);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && substr($line, 0, 2) != '--') {
                $sql .= "$line";
            }
        }

        return explode(';', $sql);
    }

    private function sendData($uid)
    {
        if (!$uid) {
            $uid = sha1($this->getUrl());
        }
        $postData = array(
            'action'             => 'interface_install',
            'uid'                => $uid,
            'plugin_version'     => SHOPGATE_PLUGIN_VERSION,
            'shopping_system_id' => (Module::getOxConfig()->getEdition() == 'CE')
                ? 96
                : 97,
            'subshops'           => $this->getSubShops(),
        );
        $this->sendPostRequest($postData);
        Module::getOxConfig()->saveShopConfVar('str', 'sg_shop_uid', $uid);
    }

    private function getSubShops()
    {
        $shops  = DatabaseProvider::getDb()->getAll("SELECT oxid FROM oxshops");
        $result = array();
        foreach ($shops as $shop) {
            $result[] = $this->getSubShop($shop[0]);
        }

        return $result;
    }

    /** @noinspection SqlNoDataSourceInspection */
    private function getSubShop($shopId)
    {
        $db = DatabaseProvider::getDb();
        /** @var Shop $shop */
        $shop = oxnew(Shop::class);
        $shop->load($shopId);
        $ordersCountStartDate = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s") . "-1 months"));

        return array(
            'uid'                 => $shopId,
            'name'                => $shop->oxshops__oxname->value,
            'url'                 => $shop->oxshops__oxurl->value,
            'contact_name'        => $shop->oxshops__oxfname->value . ' ' . $shop->oxshops__oxlname->value,
            'contact_phone'       => $shop->oxshops__oxtelefon->value,
            'contact_email'       => $shop->oxshops__oxowneremail->value,
            'stats_items'         => $db->getOne("SELECT count(oxid) FROM oxarticles WHERE oxshopid = '$shopId'"),
            'stats_categories'    => $db->getOne("SELECT count(oxid) FROM oxcategories WHERE oxshopid = '$shopId'"),
            'stats_orders'        => $db->getOne(
                "SELECT count(oxid) FROM oxorder WHERE oxshopid = '$shopId' AND oxorderdate BETWEEN '{$ordersCountStartDate}' AND now()"
            ),
            'stats_acs'           => $db->getOne("SELECT AVG(OXTOTALBRUTSUM) from oxorder WHERE oxshopid = '$shopId'"),
            'stats_currency'      => Module::getOxConfig()->getActShopCurrencyObject()->name,
            'stats_unique_visits' => '',
            'stats_mobile_visits' => '',
        );
    }

    private function getUrl()
    {
        if (isset($_SERVER)) {
            $host = $_SERVER['SERVER_NAME'];
            if (substr($host, 0, 4) == 'www.') {
                $host = substr($host, 4);
            }

            $path = explode('/admin/', $_SERVER['SCRIPT_NAME']);
            $path = trim($path[0], '/');

            $url = "http://$host/$path/";

            return $url;
        }

        return '';
    }

    private function sendPostRequest($data)
    {
        $query = http_build_query($data);
        $curl  = curl_init();
        curl_setopt($curl, CURLOPT_URL, self::SHOPGATE_REQUEST_URL);
        curl_setopt($curl, CURLOPT_POST, count($data));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if (!($result = curl_exec($curl))) {
            return false;
        }

        curl_close($curl);

        return true;
    }
}
