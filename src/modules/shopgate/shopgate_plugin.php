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

    /** @noinspection PhpIncludeInspection */
    require_once(getShopBasePath() . '/core/oxfunctions.php');
    /** @noinspection PhpIncludeInspection */
    require_once(getShopBasePath() . "/core/adodblite/adodb.inc.php");
}

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/helpers/cart.php';
require_once dirname(__FILE__) . '/helpers/redirect.php';
require_once dirname(__FILE__) . '/helpers/export/customer.php';
require_once dirname(__FILE__) . '/helpers/export/item.php';
require_once dirname(__FILE__) . '/helpers/export/settings.php';
require_once dirname(__FILE__) . '/helpers/basket.php';
require_once dirname(__FILE__) . '/helpers/user.php';
require_once dirname(__FILE__) . '/helpers/shipping.php';
require_once dirname(__FILE__) . '/helpers/voucher.php';
require_once dirname(__FILE__) . '/helpers/payment/base.php';
require_once dirname(__FILE__) . '/model/export/item.php';
require_once dirname(__FILE__) . '/model/export/category.php';
require_once dirname(__FILE__) . '/model/export/review.php';
require_once dirname(__FILE__) . '/helpers/shopgate_order_export_helper.php';
require_once dirname(__FILE__) . '/metadata.php';
require_once dirname(__FILE__) . '/shopgate_oxuser.php';

class ShopgatePluginOxid extends ShopgatePlugin
{
    const ACTION_ADD_ORDER  = 'add_order';
    const ACTION_CHECK_CART = 'check_cart';

    protected $uniqueArticleIdField = 'oxid';

    /** @var ShopgateOrderExportHelper */
    protected $orderExportHelper;

    /** @var ShopgateItemExportHelper */
    protected $itemExportHelper;

    /** @var ShopgateCartHelper */
    protected $cartHelper;

    /** @var ShopgateBasketHelper */
    protected $basketHelper;

    /** @var ShopgateUserHelper */
    protected $userHelper;

    /** @var ShopgateShippingHelper */
    protected $shippingHelper;

    /** @var ShopgateVoucherHelper */
    protected $voucherHelper;

    /**
     * stores active currency name.
     * example: EUR
     *
     * @var string
     */
    protected $_sCurrency = null;

    /** @var ShopgateConfigOxid */
    protected $config;

    private $moduleEntries44 = array(
        'oxorder'        => 'shopgate/marm_shopgate_oxorder',
        'oxoutput'       => 'shopgate/marm_shopgate_oxoutput',
        'oxarticle'      => 'shopgate/marm_shopgate_oxarticle',
        'order_overview' => 'shopgate/shopgate_order_overview',
        'order_main'     => 'shopgate/shopgate_order_overview',
        'oxbasket'       => 'shopgate/shopgate_oxbasket',
        'oxdeliverylist' => 'shopgate/shopgate_oxdeliverylist',
        'oxsession'      => 'shopgate/shopgate_oxsession',

        'marm_shopgate_article' => 'shopgate/marm_shopgate_oxadminview',
        'shopgate_order'        => 'shopgate/marm_shopgate_oxadminview',
        'shopgate_shipping'     => 'shopgate/marm_shopgate_oxadminview',
        'shopgate_payment'      => 'shopgate/marm_shopgate_oxadminview',
    );

    private $moduleEntries45 = array(
        'oxorder'        => 'shopgate/marm_shopgate_oxorder',
        'oxoutput'       => 'shopgate/marm_shopgate_oxoutput',
        'oxarticle'      => 'shopgate/marm_shopgate_oxarticle',
        'order_overview' => 'shopgate/shopgate_order_overview',
        'order_main'     => 'shopgate/shopgate_order_overview',
        'oxbasket'       => 'shopgate/shopgate_oxbasket',
        'oxdeliverylist' => 'shopgate/shopgate_oxdeliverylist',
        'oxsession'      => 'shopgate/shopgate_oxsession',
    );

    private $dbTables = array('oxordershopgate');

    private $dbColumns = array(
        'oxactions'     => array('shopgate_is_highlight' => "BOOLEAN NOT NULL DEFAULT  '0'"),
        'oxarticles'    => array(
            'marm_shopgate_marketplace' => "TINYINT UNSIGNED NOT NULL DEFAULT '1'",
            'marm_shopgate_export'      => "TINYINT UNSIGNED NOT NULL DEFAULT '1'",
        ),
        'oxdeliveryset' => array('shopgate_service_id' => 'VARCHAR(100) NULL'),
        'oxpayments'    => array('shopgate_payment_method' => 'VARCHAR(100) NULL'),
    );

    private $dbEntries = array(
        'oxpayments'    => array(
            ShopgatePaymentHelper::PAYMENT_ID_SHOPGATE,
            ShopgatePaymentHelper::PAYMENT_ID_MOBILE_PAYMENT,
        ),
        'oxdeliveryset' => array(ShopgateShippingHelper::SHIPPING_SERVICE_ID_MOBILE_SHIPPING),
    );

    public function startup()
    {
        $this->config            = marm_shopgate::getInstance()->getConfig();
        $this->orderExportHelper = new ShopgateOrderExportHelper($this->config);
        $this->itemExportHelper  = new ShopgateItemExportHelper($this->config, marm_shopgate::getInstance());
        $this->cartHelper        = new ShopgateCartHelper($this->config);
        $this->userHelper        = new ShopgateUserHelper();
        $this->shippingHelper    = new ShopgateShippingHelper();
        $this->voucherHelper     = new ShopgateVoucherHelper();
        $this->basketHelper      = new ShopgateBasketHelper(
            $this->userHelper,
            $this->voucherHelper,
            $this->shippingHelper
        );

        // Set Language for export/import
        marm_shopgate::getOxLang()->setBaseLanguage($this->config->getLanguage());

        $this->uniqueArticleIdField = $this->config->getArticleIdentifier();

        $this->checkDbColumns();

        return true;
    }

    ###################################################################################################################
    ## Plugin info / Shop info
    ###################################################################################################################

    public function createPluginInfo()
    {
        $shopId = marm_shopgate::getOxConfig()->getShopId();
        if (empty($shopId)) {
            $shopId = 'oxbaseshop';
        }
        /** @var oxShop $oOxShop */
        $oOxShop = oxNew('oxShop');
        $oOxShop->load($shopId);

        return array(
            'system_name'   => 'Oxid',
            'version'       => $oOxShop->oxshops__oxversion->value,
            'edition'       => $oOxShop->oxshops__oxedition->value,
            'shop_id'       => $oOxShop->oxshops__oxid->value,
            'plugin_health' => array(
                'module_entries' => $this->checkModuleEntries(),
                'db_tables'      => $this->checkDbTables(),
                'db_columns'     => $this->checkDbColumns(),
                'db_entries'     => $this->checkDbEntries(),
            ),
        );
    }

    private function checkModuleEntries()
    {
        $entries = $this->getModuleEntries();
        if (empty($entries)) {
            return 'OK';
        }
        $oxidModules = marm_shopgate::getOxConfig()->getConfigParam('aModules');

        $result = array();
        foreach ($entries as $key => $value) {
            $exists       = (isset($oxidModules[$key]) && stripos($oxidModules[$key], $value) !== false);
            $result[$key] = $exists
                ? 'OK'
                : 'MISSING';
        }

        return $result;
    }

    private function getModuleEntries()
    {
        if (version_compare(marm_shopgate::getOxConfig()->getVersion(), '4.6.0', ">=")) {
            return array();
        }
        if (version_compare(marm_shopgate::getOxConfig()->getVersion(), '4.5.0', ">=")) {
            return $this->moduleEntries45;
        }

        return $this->moduleEntries44;
    }

    private function checkDbTables()
    {
        $result = array();
        foreach ($this->dbTables as $table) {
            $exists         = marm_shopgate::dbGetOne("SHOW TABLES LIKE '$table'");
            $result[$table] = $exists
                ? 'OK'
                : 'MISSING';
        }

        return $result;
    }

    private function checkDbColumns()
    {
        $result = array();
        foreach ($this->dbColumns as $table => $columns) {
            foreach ($columns as $column => $type) {
                $exists                   = $this->checkDbColumn($table, $column, $type);
                $result["$table.$column"] = $exists
                    ? 'OK'
                    : 'MISSING';
            }
        }

        return $result;
    }

    /**
     * @param string $table
     * @param string $column
     * @param string $type
     *
     * @return bool
     */
    private function checkDbColumn($table, $column, $type)
    {
        try {
            return
                (bool)marm_shopgate::dbGetOne("SHOW COLUMNS FROM $table WHERE field = '$column'")
                || (bool)marm_shopgate::dbExecute(
                    "ALTER TABLE `$table` ADD COLUMN `$column` $type"
                );
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * @return array
     */
    private function checkDbEntries()
    {
        $result = array();
        foreach ($this->dbEntries as $table => $oxIDs) {
            foreach ($oxIDs as $oxID) {
                $exists                 = marm_shopgate::dbGetOne("SELECT oxid FROM $table WHERE oxid = '$oxID'");
                $result["$table|$oxID"] = $exists
                    ? 'OK'
                    : 'MISSING';
            }
        }

        return $result;
    }

    public function createShopInfo()
    {
        return array(
            'category_count'    => marm_shopgate::dbGetOne('SELECT count(OXID) FROM oxcategories'),
            'item_count'        => marm_shopgate::dbGetOne('SELECT count(OXID) FROM oxarticles'),
            'review_count'      => marm_shopgate::dbGetOne('SELECT count(OXID) FROM oxreviews'),
            'plugins_installed' => $this->getInstalledPlugins(),
        );
    }

    /**
     * @return array
     */
    private function getInstalledPlugins()
    {
        $result = array();

        // oxModuleList only exists in Oxid >= 4.6
        if (class_exists('oxModuleList')) {
            /** @var oxModuleList $oxModuleList */
            $oxModuleList = oxNew('oxModuleList');

            /** @var oxModule[] $modules */
            $modules = $oxModuleList->getModulesFromDir(marm_shopgate::getOxConfig()->getModulesDir());
            foreach ($modules as $module) {
                $result[] = array(
                    'id'        => $module->getId(),
                    'name'      => $module->getTitle(),
                    'author'    => $module->getInfo('author'),
                    'version'   => $module->getInfo('version'),
                    'is_active' => $module->isActive(),
                );
            }

            return $result;
        }

        $moduleVersions = marm_shopgate::getOxConfig()->getConfigParam('aModuleVersions');
        foreach ($moduleVersions as $key => $version) {
            $result[] = array(
                'id'      => $key,
                'version' => $version,
            );
        }

        return $result;
    }

    ###################################################################################################################
    ## Cron
    ###################################################################################################################

    /**
     * @inheritdoc
     * @throws ShopgateLibraryException
     */
    public function cron($jobname, $params, &$message, &$errorcount)
    {
        switch ($jobname) {
            case 'set_shipping_completed':
                $this->cronSetShippingCompleted($message, $errorcount);
                break;
            case 'clean_orders':
                $this->cronCleanOrders($message, $errorcount);
                break;
            case 'cancel_orders':
                $this->cronCancelOrders($message, $errorcount);
                break;
            default:
                throw new ShopgateLibraryException(
                    ShopgateLibraryException::PLUGIN_CRON_UNSUPPORTED_JOB,
                    "{$jobname}",
                    true
                );
        }
    }

    /**
     * @param string $message
     * @param int    $errorcount
     */
    protected function cronCleanOrders(&$message, &$errorcount)
    {
        /** @var oxOrderShopgate $oBaseOxOrderShopgate */
        $oBaseOxOrderShopgate = oxNew('oxordershopgate');
        /** @var oxOrder $oxOrder */
        $oxOrder = oxNew('oxOrder');

        $qry = "SELECT so.oxid
				FROM {$oBaseOxOrderShopgate->getViewName()} so
				LEFT JOIN {$oxOrder->getViewName()} o ON ( so.oxorderid = o.oxid)
				WHERE o.oxid IS NULL";

        $oldOrders = marm_shopgate::dbGetAll($qry);
        if (!empty($oldOrders)) {
            foreach ($oldOrders as $oldOrder) {
                $oxId = array_shift($oldOrder);
                if (!$oBaseOxOrderShopgate->delete($oxId)) {
                    $message .= __FUNCTION__ . "(): couldn't delete order with oxid=$oxId";
                    $errorcount++;
                }
            }
            $message .= __FUNCTION__ . "(): deleted " . count($oldOrders) . " orders \n";
        } else {
            $message .= __FUNCTION__ . "(): no orders to delete\n";
        }
    }

    /**
     * @param string $message
     * @param int    $errorcount
     *
     * @throws ShopgateLibraryException
     */
    protected function cronSetShippingCompleted(&$message, &$errorcount)
    {
        /** @var oxOrderShopgate $oBaseOxOrderShopgate */
        $oBaseOxOrderShopgate = oxNew('oxOrderShopgate');
        /** @var oxOrder $oxOrder */
        $oxOrder = oxNew('oxOrder');

        $qry = "SELECT os.OXID
				FROM {$oBaseOxOrderShopgate->getViewName()} os
				JOIN {$oxOrder->getViewName()} o ON o.oxid = os.oxorderid
				WHERE os.is_sent_to_shopgate = 0
				AND o.oxsenddate != '0000-00-00 00:00:00'";

        $results = marm_shopgate::dbGetAll($qry);

        if (!empty($results)) {
            $count = 0;
            foreach ($results as $rs) {
                $shopgateOrder = clone $oBaseOxOrderShopgate;
                try {
                    $shopgateOrder->load($rs['OXID']);
                    $shopgateOrder->confirmShipping();
                    $count++;
                } catch (Exception $e) {
                    $orderNumber = $shopgateOrder->oxordershopgate__order_number->value;
                    $msg         = utf8_encode($e->getMessage());
                    $message     .= __FUNCTION__ . "(): Error confirming shipping for order #{$orderNumber}: $msg \n";
                    $errorcount++;
                }
            }
            $message .= __FUNCTION__ . "(): confirmed shipping for $count orders \n";
        } else {
            $message .= __FUNCTION__ . "(): no orders that need shipping confirmation \n";
        }
    }

    /**
     * @param string $message
     * @param int    $errorcount
     */
    protected function cronCancelOrders(&$message, &$errorcount)
    {
        /** @var oxOrderShopgate $oBaseOxOrderShopgate */
        $oBaseOxOrderShopgate = oxNew('oxOrderShopgate');
        /** @var oxOrder $oxOrder */
        $oxOrder = oxNew('oxOrder');
        /** @var oxOrderArticle $oxOrderArtcile */
        $oxOrderArtcile = oxNew('oxOrderArticle');

        $qry = "SELECT DISTINCT so.oxid
				FROM {$oBaseOxOrderShopgate->getViewName()} so
				JOIN {$oxOrder->getViewName()} o ON ( so.oxorderid = o.oxid )
				JOIN {$oxOrderArtcile->getViewName()} oa ON ( oa.oxorderid = o.oxid )
				WHERE so.is_cancellation_sent_to_shopgate = 0 AND ( o.oxstorno = 1 OR oa.oxstorno = 1 )";

        $result = marm_shopgate::dbGetAll($qry);
        if (!empty($result)) {
            $count = 0;
            foreach ($result as $sShopagteOrderOxid) {
                $shopgateOrder = clone $oBaseOxOrderShopgate;
                $shopgateOrder->load($sShopagteOrderOxid["oxid"]);

                try {
                    $shopgateOrder->cancelOrder();
                    $count++;
                } catch (Exception $e) {
                    $errorcount++;
                    $orderNumber = $shopgateOrder->oxordershopgate__order_number->value;
                    $msg         = utf8_encode($e->getMessage());
                    $message     .= __FUNCTION__ . "(): Error cancelling order #{$orderNumber}: $msg \n";
                }
            }
            $message .= __FUNCTION__ . "(): canceled $count orders \n";
        } else {
            $message .= __FUNCTION__ . "(): no orders to cancel \n";
        }
    }

    ###################################################################################################################
    ## Items Export
    ###################################################################################################################

    /**
     * @param bool  $parentsOnly
     * @param int   $limit
     * @param int   $offset
     * @param array $uids
     *
     * @return array
     */
    protected function getExportArticleIds($parentsOnly, $limit, $offset, array $uids)
    {
        /** @var oxArticle $oxArticle */
        $oxArticle        = oxNew('oxArticle');
        $articleTable     = $oxArticle->getViewName();
        $sqlActiveSnippet = $oxArticle->getSqlActiveSnippet() . " AND marm_shopgate_export = 1 AND OXID != ' '";

        if ($parentsOnly) {
            $sqlActive = $sqlActiveSnippet . " AND OXPARENTID = ''";
        } else {
            $sqlActive = $sqlActiveSnippet . " AND (OXPARENTID = '' OR OXPARENTID IN (SELECT OXID FROM {$articleTable} WHERE {$sqlActiveSnippet}))";
        }

        $sql = "SELECT `OXID` FROM `{$articleTable}` WHERE {$sqlActive}";
        if (!empty($uids)) {
            $implodedUids = "'" . implode("','", $uids) . "'";
            $sql          .= " AND (OXID in ($implodedUids) OR OXARTNUM in ($implodedUids))";
        }
        $sql .= " ORDER BY OXARTNUM ASC";
        $sql .= !empty($limit)
            ? " LIMIT $limit"
            : '';
        $sql .= !empty($offset)
            ? " OFFSET $offset"
            : '';

        $ids    = marm_shopgate::dbGetAll($sql);
        $result = array();
        foreach ($ids as $id) {
            $result[] = array_shift($id);
        }

        return $result;
    }

    public function createItemsCsv()
    {
        $start = microtime(true);
        $this->log(__FUNCTION__ . ' invoked', ShopgateLogger::LOGTYPE_DEBUG);
        set_time_limit(10 * 60);

        if (!empty($_REQUEST['display_errors'])) {
            ini_set('display_errors', 'stdout');
            error_reporting(E_ALL ^ E_NOTICE);
        }
        $aItemNumbers = !empty($_REQUEST['item_numbers'])
            ? $_REQUEST['item_numbers']
            : array();

        $oArticleBase = $this->itemExportHelper->getArticleBase();

        $ids = $this->getExportArticleIds(true, $this->exportLimit, $this->exportOffset, $aItemNumbers);
        $this->itemExportHelper->init($ids);

        foreach ($ids as $id) {
            $oArticle = clone $oArticleBase;
            $oArticle->load($id);
            $this->log(
                "## ITEM: {$oArticle->oxarticles__oxtitle->value} ({$oArticle->oxarticles__oxartnum->value})",
                ShopgateLogger::LOGTYPE_DEBUG
            );

            $oArticle->sg_act_as_child = false;

            $item = $this->buildItem($oArticle, false);
            $this->addItemRow($item->asCsv());
            foreach ($item->getChildren() as $child) {
                /** @var Shopgate_Model_Export_Product $child */
                $this->addItemRow($child->asCsv());
            }

            $oArticle = null;
        }
        $time = microtime(true) - $start;
        $this->log(__FUNCTION__ . " done after {$time}s, memory: " . memory_get_usage(), ShopgateLogger::LOGTYPE_DEBUG);
    }

    public function createItems($limit = null, $offset = null, array $uids = array())
    {
        $start = microtime(true);
        $this->log(__FUNCTION__ . ' invoked', ShopgateLogger::LOGTYPE_DEBUG);
        set_time_limit(10 * 60);

        if (!empty($_REQUEST['display_errors'])) {
            ini_set('display_errors', 'stdout');
            error_reporting(E_ALL ^ E_NOTICE);
        }

        $this->useTaxClasses();
        $oArticleBase = $this->itemExportHelper->getArticleBase();

        $ids = $this->getExportArticleIds(true, $limit, $offset, $uids);
        $this->itemExportHelper->init($ids);

        foreach ($ids as $id) {
            $oxArticle = clone $oArticleBase;
            $oxArticle->load($id);
            $this->log(
                "## ITEM: {$oxArticle->oxarticles__oxtitle->value} ({$oxArticle->oxarticles__oxartnum->value})",
                ShopgateLogger::LOGTYPE_DEBUG
            );

            $oxArticle->sg_act_as_child = false;

            $item = $this->buildItem($oxArticle, false);
            $this->addItemModel($item);

            $oxArticle = null;
        }
        $time = microtime(true) - $start;
        $this->log(__FUNCTION__ . " done after {$time}s, memory: " . memory_get_usage(), ShopgateLogger::LOGTYPE_DEBUG);
    }

    /**
     * @param oxArticle $oxArticle
     * @param bool      $isChild
     *
     * @return Shopgate_Model_Export_Product
     */
    private function buildItem($oxArticle, $isChild)
    {
        $item = new Shopgate_Model_Export_Product($this->itemExportHelper);
        $item->setItem($oxArticle);
        $item->setUseTaxClasses($this->useTaxClasses);
        $item->setIsChild($isChild);
        $item->generateData();

        return $item;
    }

    ###################################################################################################################
    ## Categories Export
    ###################################################################################################################

    public function createCategoriesCsv()
    {
        $start = microtime(true);
        $this->log(__FUNCTION__ . ' invoked', ShopgateLogger::LOGTYPE_DEBUG);

        $maxSort = $this->getCategoryMaxSort();
        $ids     = $this->getCategoryIds($this->exportLimit, $this->exportOffset);
        foreach ($ids as $id) {
            $category = $this->buildCategory(array_shift($id), $maxSort);
            $this->addCategoryRow($category->asCsv());
        }

        $time = microtime(true) - $start;
        $this->log(__FUNCTION__ . " done after {$time}s, memory: " . memory_get_usage(), ShopgateLogger::LOGTYPE_DEBUG);
    }

    public function createCategories($limit = null, $offset = null, array $uids = array())
    {
        $start = microtime(true);
        $this->log(__FUNCTION__ . ' invoked', ShopgateLogger::LOGTYPE_DEBUG);

        $maxSort = $this->getCategoryMaxSort();
        $ids     = $this->getCategoryIds($limit, $offset, $uids);
        foreach ($ids as $id) {
            $category = $this->buildCategory(array_shift($id), $maxSort);
            $this->addCategoryModel($category);
        }

        $time = microtime(true) - $start;
        $this->log(__FUNCTION__ . " done after {$time}s, memory: " . memory_get_usage(), ShopgateLogger::LOGTYPE_DEBUG);
    }

    /**
     * @return int maximum sort value for categories
     */
    private function getCategoryMaxSort()
    {
        /** @var oxCategory $oxCategory */
        $oxCategory = oxNew('oxCategory');

        return marm_shopgate::dbGetOne(
            "SELECT MAX(oxsort) FROM {$oxCategory->getViewName()} WHERE {$oxCategory->getSqlActiveSnippet()}"
        );
    }

    /**
     * @param string $id
     * @param int    $maxSort
     *
     * @return Shopgate_Model_Export_Category
     */
    private function buildCategory($id, $maxSort)
    {
        /** @var oxCategory $oxCategory */
        $oxCategory = oxNew('oxCategory');
        $oxCategory->load($id);

        $category = new Shopgate_Model_Export_Category();
        $category->setConfig($this->config);
        $category->setMaxSort($maxSort);
        $category->setItem($oxCategory);

        return $category->generateData();
    }

    /**
     * @param int   $limit
     * @param int   $offset
     * @param array $uids
     *
     * @return array
     */
    private function getCategoryIds($limit, $offset, array $uids = array())
    {
        /** @var oxCategory $oxCategory */
        $oxCategory = oxNew('oxCategory');
        $sql        = "SELECT oxid FROM {$oxCategory->getViewName()} WHERE oxactive = 1";
        $sql        .= !empty($uids)
            ? " AND oxid in ('" . implode("','", $uids) . "')"
            : '';
        $sql        .= !empty($limit)
            ? " LIMIT $limit"
            : '';
        $sql        .= !empty($offset)
            ? " OFFSET $offset"
            : '';

        return marm_shopgate::dbGetAll($sql);
    }


    ###################################################################################################################
    ## Reviews Export
    ###################################################################################################################

    public function createReviewsCsv()
    {
        $start = microtime(true);
        $this->log(__FUNCTION__ . ' invoked', ShopgateLogger::LOGTYPE_DEBUG);
        set_time_limit(10 * 60);

        $ids = $this->getReviewIds($this->exportLimit, $this->exportOffset);
        foreach ($ids as $id) {
            $this->addReviewRow($this->buildReview(array_shift($id))->asCsv());
        }

        $time = microtime(true) - $start;
        $this->log(__FUNCTION__ . " done after {$time}s, memory: " . memory_get_usage(), ShopgateLogger::LOGTYPE_DEBUG);
    }

    public function createReviews($limit = null, $offset = null, array $uids = array())
    {
        $start = microtime(true);
        $this->log(__FUNCTION__ . ' invoked', ShopgateLogger::LOGTYPE_DEBUG);

        $ids = $this->getReviewIds($limit, $offset, $uids);
        foreach ($ids as $id) {
            $this->addReviewModel($this->buildReview(array_shift($id)));
        }

        $time = microtime(true) - $start;
        $this->log(__FUNCTION__ . " done after {$time}s, memory: " . memory_get_usage(), ShopgateLogger::LOGTYPE_DEBUG);
    }

    private function buildReview($id)
    {
        /** @var oxReview $oxReview */
        $oxReview = oxNew('oxReview');
        $oxReview->load($id);

        $review = new Shopgate_Model_Export_Review();
        $review->setUniqueArticleIdentifier($this->config->getArticleIdentifier());
        $review->setItem($oxReview);

        return $review->generateData();
    }

    /**
     * @param int   $limit
     * @param int   $offset
     * @param array $uids
     *
     * @return array
     */
    private function getReviewIds($limit = null, $offset = null, array $uids = array())
    {
        /** @var oxReview $oxReview */
        $oxReview = oxNew('oxReview');
        $sql      = "SELECT oxid FROM {$oxReview->getViewName()} WHERE oxobjectid != ''";
        $sql      .= marm_shopgate::getOxConfig()->getConfigParam('blGBModerate')
            ? " AND OXACTIVE = 1"
            : '';
        $sql      .= !empty($uids)
            ? " AND oxid in ('" . implode("','", $uids) . "')"
            : '';
        $sql      .= !empty($limit)
            ? " LIMIT $limit"
            : '';
        $sql      .= !empty($offset)
            ? " OFFSET $offset"
            : '';

        return marm_shopgate::dbGetAll($sql);
    }

    ###################################################################################################################
    ## Get customer
    ###################################################################################################################

    public function getCustomer($user, $pass)
    {
        /** @var oxUser $oxUser */
        $oxUser = oxNew('oxUser');
        try {
            $oxUser->login($user, $pass);
        } catch (Exception $e) {
            if ($e->getMessage() != 'ERROR_MESSAGE_USER_NOVALIDLOGIN') {
                $this->log(
                    'caught ' . get_class($e) . ', message: ' . $e->getMessage() . ", trace:\n" . $e->getTraceAsString()
                );
            }
            throw new ShopgateLibraryException(
                ShopgateLibraryException::PLUGIN_WRONG_USERNAME_OR_PASSWORD,
                null,
                false,
                false
            );
        }

        $helper = new ShopgateCustomerExportHelper($this->config);

        return $helper->buildExternalCustomer($oxUser);
    }

    ###################################################################################################################
    ## Order
    ###################################################################################################################

    public function addOrder(ShopgateOrder $order)
    {
        $this->log(__FUNCTION__ . ' invoked', ShopgateLogger::LOGTYPE_DEBUG);
        $this->checkPluginStatus();
        $this->basketHelper->setErrorOnInvalidCoupon(true);

        $existingId = $this->getOxOrderId($order);
        if (!empty($existingId)) {
            $msg = "external_order_id: $existingId";
            /** @var oxOrder $oxOrder */
            $oxOrder = oxNew('oxOrder');
            if ($oxOrder->load($existingId)) {
                $msg .= ", external_order_number: " . $oxOrder->oxorder__oxordernr->value;
            }
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER, $msg, true);
        }
        $this->checkIfArticlesExist($order);

        /** @var ShopgateOrder $order */
        $order = $order->utf8Decode($this->config->getEncoding());

        $order = $this->convertShopgateCoupons($order);

        try {
            $oxBasket = $this->basketHelper->buildOxBasket($order);
        } catch (Exception $e) {
            $msg = $this->getErrorMessage($e) . ' (order not imported)';
            $this->log($msg . ", trace:\n" . $e->getTraceAsString(), ShopgateLogger::LOGTYPE_ERROR);
            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, $msg, true, false);
        }
        if (in_array(
            $oxBasket->getPaymentId(),
            array(
                ShopgatePaymentHelper::PAYMENT_ID_PAYOLUTION_INVOICE_B2C,
                ShopgatePaymentHelper::PAYMENT_ID_PAYOLUTION_INSTALLMENT,
            )
        )) {
            // temporarily setting the payment type to "mobile_payment", so that no Payolution logic is triggered during finalizeOrder
            $oxBasket->setPayment(ShopgatePaymentHelper::PAYMENT_ID_MOBILE_PAYMENT);
        }

        $this->log("oxBasket created - try to finalizeOrder", ShopgateLogger::LOGTYPE_DEBUG);

        if (class_exists('order') && method_exists('order', 'getDeliveryAddressMD5')) {
            /** @var order $oxidOrderManager */
            $oxidOrderManager = oxNew('order');
            $oxidOrderManager->setUser($oxBasket->getUser());
            // this prevents the "ORDER_STATE_INVALIDDElADDRESSCHANGED" error
            $_POST['sDeliveryAddressMD5'] = $oxidOrderManager->getDeliveryAddressMD5();
        }

        /** @var oxOrderShopgate $shopgateOrder */
        $shopgateOrder                                       = oxNew('oxOrderShopgate');
        $shopgateOrder->oxordershopgate__oxorderid           = new oxField('0', oxField::T_RAW);
        $shopgateOrder->oxordershopgate__order_number        = new oxField($order->getOrderNumber(), oxField::T_RAW);
        $shopgateOrder->oxordershopgate__is_paid             = new oxField($order->getIsPaid(), oxField::T_RAW);
        $shopgateOrder->oxordershopgate__is_shipping_blocked = new oxField(
            $order->getIsShippingBlocked(),
            oxField::T_RAW
        );
        $shopgateOrder->oxordershopgate__order_data          = new oxField(
            base64_encode(serialize($order)),
            oxField::T_RAW
        );
        $shopgateOrder->save();

        /** @var oxOrder $oxOrder */
        $oxOrder = oxNew('oxOrder');
        try {
            $iStatus = $oxOrder->finalizeOrder($oxBasket, $oxBasket->getUser(), false);
        } catch (Exception $e) {
            $oxOrder->delete();
            $shopgateOrder->delete();
            $msg = $this->getErrorMessage($e) . ' (order not imported)';
            $this->log($msg . ", trace:\n" . $e->getTraceAsString(), ShopgateLogger::LOGTYPE_ERROR);
            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, $msg, true, false);
        }

        $shopgateOrder->oxordershopgate__oxorderid = new oxField($oxOrder->oxorder__oxid->value, oxField::T_RAW);
        $shopgateOrder->save();

        $oxBasket->deleteBasket();

        $warnings = array();
        if ($iStatus != 1) { #oxOrder::ORDER_STATE_OK (constant not defined in Oxid 4.1)
            $sStatus = '';
            if (is_numeric($iStatus)) {
                $oxOrderRef = new ReflectionClass("oxOrder");
                $constants  = $oxOrderRef->getConstants();
                $sStatus    = array_search($iStatus, $constants);
            } elseif ($iStatus === null) {
                $sStatus = 'null';
            } elseif ($iStatus === '') {
                $sStatus = 'empty string';
            }

            $msg = "finalizeOrder() failed, status code: {$iStatus} ({$sStatus}) ";
            if (is_numeric($iStatus)) {
                $oxOrder->delete();
                throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, $msg, true);
            }
            # If finalizeOrder returns a non-numeric value, we ignore it, cause this normally never happens in Oxid.
            # So it must be caused by a third-party plugin and we don't know how to handle that.

            $msg = "finalizeOrder() returned {$iStatus} ({$sStatus})";
            $this->log($msg, ShopgateLogger::LOGTYPE_ERROR);
            $warnings[] = array('code' => 1100, 'message' => $msg);
        }

        $this->log("oxOrder finalized successfully - start to modify order", ShopgateLogger::LOGTYPE_DEBUG);

        try {
            $this->loadOrderPaid($oxOrder, $order);
            $this->loadOrderPaymentInfos($oxOrder, $order);
            $this->loadOrderFolder($oxOrder, $order);
            $this->loadOrderAdditionalInfo($oxOrder);
            $this->loadOrderRemark($oxOrder, $order);
            $this->loadOrderCustomFields($oxOrder, $order);
        } catch (Exception $e) {
            $oxOrder->delete();
            $msg = $this->getErrorMessage($e) . ' (order not imported)';
            $this->log($msg . ", trace:\n" . $e->getTraceAsString(), ShopgateLogger::LOGTYPE_ERROR);
            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, $msg, true, false);
        }

        $oxOrder->save();

        $totalShopgate = $order->getAmountComplete();
        $totalOxid     = $oxOrder->getTotalOrderSum();
        $currency      = $order->getCurrency();

        if (abs($totalShopgate - $totalOxid) > 0.02) {
            $msg        = "differing total order amounts:\n";
            $msg        .= "\tShopgate:\t$totalShopgate $currency \n";
            $msg        .= "\tOXID:\t$totalOxid $currency\n";
            $warnings[] = array("code" => 1100, "message" => $msg);
        }

        return array(
            'external_order_id'     => $oxOrder->oxorder__oxid->value,
            'external_order_number' => $oxOrder->oxorder__oxordernr->value,
            'warnings'              => $warnings,
        );
    }

    /**
     * Check if the ordered articles exist in the db. (No matter if they are active or available.)
     *
     * @param ShopgateOrder $order
     *
     * @throws ShopgateLibraryException
     */
    private function checkIfArticlesExist(ShopgateOrder $order)
    {
        $notFoundIds = array();
        foreach ($order->getItems() as $item) {
            $info = $this->jsonDecode($item->getInternalOrderInfo(), true);
            if (!empty($info['article_oxid'])) {
                /** @var oxArticle $oxArticle */
                $oxArticle = oxNew('oxArticle');
                $table     = $oxArticle->getViewName();
                $sql       = "SELECT OXID FROM $table WHERE OXID = ?";
                $oxid      = marm_shopgate::dbGetOne($sql, array($info['article_oxid']));
                if (empty($oxid)) {
                    $artnum = $item->getItemNumber();
                    if ($artnum != $info['article_oxid']) {
                        $notFoundIds[] = "$artnum (ID: {$info['article_oxid']})";
                    } else {
                        $notFoundIds[] = $info['article_oxid'];
                    }
                }
            }
        }
        if (!empty($notFoundIds)) {
            $msg = "the following articles don't exist (anymore): " . implode(', ', $notFoundIds);
            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, $msg, true);
        }
    }

    /**
     * @param Exception $e
     *
     * @return string
     */
    private function getErrorMessage(Exception $e)
    {
        $trace    = $e->getTrace();
        $class    = $trace[0]['class'];
        $function = $trace[0]['function'];
        $line     = $e->getLine();
        $msg      = utf8_encode($e->getMessage());
        /** @noinspection PhpUndefinedClassInspection */
        if (class_exists('Payolution_Error') && $e instanceof Payolution_Error) {
            /** @noinspection PhpUndefinedMethodInspection */
            $msg .= ', ' . $e->responseError()->message;
        }

        return
            'caught ' .
            get_class($e) .
            " [thrown in $class::$function() on line $line, Code: {$e->getCode()}, message: $msg]";
    }

    public function updateOrder(ShopgateOrder $order)
    {
        $this->checkPluginStatus();

        $existingId = $this->getOxOrderId($order);
        if (empty($existingId)) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_ORDER_NOT_FOUND);
        }

        /** @var ShopgateOrder $order */
        $order = $order->utf8Decode($this->config->getEncoding());

        /** @var oxOrderShopgate $oOxidOrderShopgate */
        $oOxidOrderShopgate = oxNew('oxOrderShopgate');
        $oOxidOrderShopgate->load($order->getOrderNumber(), "order_number");

        /** @var oxOrder $oOxidOrder */
        $oOxidOrder = oxNew('oxOrder');

        $qry = "SELECT oxorderid FROM {$oOxidOrderShopgate->getViewName()} WHERE order_number = '{$order->getOrderNumber()}'";

        $sOxidOrderId = marm_shopgate::dbGetOne($qry);

        $oOxidOrder->load($sOxidOrderId);

        $isValidShipping = true;
        if (($order->getIsShippingBlocked() // If shipping is blocked
                || (bool)$oOxidOrderShopgate->oxordershopgate__is_shipping_blocked->value) // shipping was blocked
            && strtotime($oOxidOrder->oxorder__oxsenddate->value) > 0
        ) { // AND is sent

            $isValidShipping = false;
        }

        // Update payment
        if ($order->getUpdatePayment() || $this->config->getUnblockedOrdersAsPaid()) {
            $this->loadOrderPaid($oOxidOrder, $order);
            $oOxidOrderShopgate->oxordershopgate__is_paid = new oxField($order->getIsPaid(), oxField::T_RAW);
        }

        // Update shipping
        if ($order->getUpdateShipping()) {
            if ($isValidShipping) {
                $this->loadOrderFolder($oOxidOrder, $order);
                $this->loadOrderRemark($oOxidOrder, $order);
            }
            $oOxidOrderShopgate->oxordershopgate__is_shipping_blocked = new oxField(
                $order->getIsShippingBlocked(),
                oxField::T_RAW
            );
        }

        $oOxidOrder->save();

        $oOxidOrderShopgate->oxordershopgate__order_data = new oxField(
            base64_encode(serialize($order)), oxField::T_RAW
        );
        $oOxidOrderShopgate->save();

        // Error on invalid shipping state
        if (!$isValidShipping) {
            throw new ShopgateLibraryException(ShopgateLibraryException::PLUGIN_ORDER_STATUS_IS_SENT);
        }

        $info = array(
            'external_order_id'     => $oOxidOrder->oxorder__oxid->value,
            'external_order_number' => $oOxidOrder->oxorder__oxordernr->value,
        );

        return $info;
    }

    //#########################
    // Order Bakset
    //#########################

    /**
     * Convert items with negative amount to Shopgate coupons
     *
     * @param ShopgateOrder $order
     *
     * @return ShopgateOrder
     */
    protected function convertShopgateCoupons(ShopgateOrder $order)
    {
        $items   = array();
        $coupons = array();

        foreach ($order->getItems() as $item) {
            if ($item->isSgCoupon()) {
                $coupon = new ShopgateShopgateCoupon();

                $code    = $item->getItemNumber();
                $matches = array();
                if (preg_match("/.*Code\: (?P<code>.+)$/i", $item->getName(), $matches)) {
                    $code = $matches["code"];
                    $code = trim($code, "()");
                }

                $coupon->setCode($code);
                $coupon->setAmountGross(abs($item->getUnitAmountWithTax()));
                $coupon->setAmountNet(abs($item->getUnitAmount()));
                $coupon->setDescription($item->getName());
                $coupon->setCurrency($item->getCurrency());

                $coupons[] = $coupon;
            } else {
                $items[] = $item;
            }
        }

        $order->setShopgateCoupons($coupons);
        $order->setItems($items);

        return $order;
    }

    /**
     * @param oxOrder       $oxOrder
     * @param ShopgateOrder $shopgateOrder
     */
    protected function loadOrderPaid(oxOrder &$oxOrder, ShopgateOrder $shopgateOrder)
    {
        if (!empty($oxOrder->oxorder__oxpaid->value) && strtotime($oxOrder->oxorder__oxpaid->value) > 0) {
            return;
        }

        if ($shopgateOrder->getIsPaid()) {
            $oxOrder->oxorder__oxpaid = new oxField($shopgateOrder->getPaymentTime("Y-m-d H:i:s"), oxField::T_RAW);
        } elseif ($this->config->getUnblockedOrdersAsPaid()
            && $shopgateOrder->getPaymentGroup() == ShopgateOrder::SHOPGATE
            && !$shopgateOrder->getIsShippingBlocked()
        ) {
            $oxOrder->oxorder__oxpaid = new oxField(date("Y-m-d H:m:s"), oxField::T_RAW);
        }
        $oxOrder->save();
    }

    /**
     * @param oxOrder       $oxOrder
     * @param ShopgateOrder $shopgateOrder
     */
    protected function loadOrderFolder(oxOrder &$oxOrder, ShopgateOrder $shopgateOrder)
    {
        $folder = $this->config->getOrderfolderBlocked();
        if (!$shopgateOrder->getIsShippingBlocked()) {
            $folder = $this->config->getOrderfolderUnblocked();
        }
        $oxOrder->oxorder__oxfolder = new oxField($folder, oxField::T_RAW);
        $oxOrder->save();
    }

    /**
     * @param oxOrder       $oxOrder
     * @param ShopgateOrder $shopgateOrder
     */
    protected function loadOrderRemark(oxOrder &$oxOrder, ShopgateOrder $shopgateOrder)
    {
        $remarks = array();
        if (!$this->config->getSuppressOrderNotes()) {
            if ($shopgateOrder->getIsShippingBlocked()) {
                $remarks[] = "ACHTUNG: Diese Bestellung darf noch nicht versendet werden!";
            }
            if ($shopgateOrder->getIsCustomerInvoiceBlocked()) {
                $remarks[] = "ACHTUNG: Hier darf keine Rechnung beigelegt werden!";
            }
        }
        if ($shopgateOrder->getIsTest()) {
            $remarks[] = "## Dies ist eine Testbestellung!";
        }

        $oxOrder->oxorder__oxremark = new oxField(implode(' - ', $remarks), oxField::T_RAW);
        $oxOrder->save();
    }

    /**
     * @param oxOrder       $oxOrder
     * @param ShopgateOrder $shopgateOrder
     */
    protected function loadOrderCustomFields(oxOrder &$oxOrder, ShopgateOrder $shopgateOrder)
    {
        $customFields = $shopgateOrder->getCustomFields();
        if (empty($customFields)) {
            return;
        }
        foreach ($customFields as $customField) {
            $fieldName = $customField->getInternalFieldName();
            if (isset($oxOrder->{"oxorder__$fieldName"})) {
                $oxOrder->{"oxorder__$fieldName"}->value = $customField->getValue();
            }
        }
        $oxOrder->save();
    }

    /**
     * @param oxOrder $oxOrder
     */
    protected function loadOrderAdditionalInfo(oxOrder &$oxOrder)
    {
        $oxOrder->oxorder__oxlang        = new oxField($oxOrder->getOrderLanguage());
        $oxOrder->oxorder__oxtransstatus = new oxField('FROM_SHOPGATE', oxField::T_RAW);
        $oxOrder->oxorder__oxip          = new oxField("shopgate.com", oxField::T_RAW);
        $oxOrder->save();
    }

    /**
     * @param oxOrder       $oxOrder
     * @param ShopgateOrder $shopgateOrder
     */
    protected function loadOrderPaymentInfos(oxOrder &$oxOrder, ShopgateOrder $shopgateOrder)
    {
        $paymentHelper = ShopgatePaymentHelper::createInstance($shopgateOrder, $oxOrder);

        $paymentHelper->loadOrderPaymentInfos();
        $paymentHelper->createOxUserPayment();
        $paymentHelper->createSpecificData();

        $oxOrder->save();
    }

    //#########################
    // Helper Functions
    //#########################

    /**
     * returns active currency name
     *
     * @example: EUR
     * @return string
     */
    protected function getActiveCurrency()
    {
        if ($this->_sCurrency !== null) {
            return $this->_sCurrency;
        }
        $oCur             = marm_shopgate::getOxConfig()->getActShopCurrencyObject();
        $this->_sCurrency = $oCur->name;

        return $this->_sCurrency;
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return int oxid
     */
    protected function getOxOrderId(ShopgateOrder $shopgateOrder)
    {
        return marm_shopgate::dbGetOne(
            "SELECT oxid FROM oxordershopgate WHERE order_number = ?",
            array($shopgateOrder->getOrderNumber())
        );
    }

    ###################################################################################################################
    ## Orders Export
    ###################################################################################################################

    public function getOrders(
        $customerToken,
        $customerLanguage,
        $limit = 10,
        $offset = 0,
        $orderDateFrom = '',
        $sortOrder = 'created_desc'
    ) {
        $this->orderExportHelper->setLang(substr($customerLanguage, 0, 2));
        $ids    = $this->getOrderIds($customerToken, $limit, $offset, $orderDateFrom, $sortOrder);
        $result = array();
        foreach ($ids as $id) {
            $id = array_shift($id);
            /** @var oxOrder $oxOrder */
            $oxOrder = oxNew('oxOrder');
            $oxOrder->load($id);
            $result[] = $this->orderExportHelper->buildExternalOrder($oxOrder);
        }

        return $result;
    }

    /**
     * @param string $customerToken
     * @param int    $limit
     * @param int    $offset
     * @param string $orderDateFrom
     * @param string $sortOrder
     *
     * @return array
     */
    private function getOrderIds(
        $customerToken,
        $limit = 10,
        $offset = 0,
        $orderDateFrom = '',
        $sortOrder = 'created_desc'
    ) {
        switch ($sortOrder) {
            case 'created_desc':
                $orderBy = 'oxorderdate DESC';
                break;
            case 'created_asc':
                $orderBy = 'oxorderdate';
                break;
            default:
                $orderBy = '';
        }

        $sql = "SELECT oxid FROM oxorder WHERE oxuserid = '$customerToken'";
        $sql .= !empty($orderDateFrom)
            ? " AND oxorderdate >= '$orderDateFrom'"
            : '';
        $sql .= !empty($orderBy)
            ? " ORDER BY $orderBy"
            : '';
        $sql .= !empty($limit)
            ? " LIMIT $limit"
            : '';
        $sql .= !empty($offset)
            ? " OFFSET $offset"
            : '';
        $sql .= !empty($offset)
            ? " OFFSET $offset"
            : '';

        return marm_shopgate::dbGetAll($sql);
    }

    ###################################################################################################################
    ## Cart validation
    ###################################################################################################################

    public function checkCart(ShopgateCart $cart)
    {
        $this->cleanUpCheckCartUsers();
        $this->basketHelper->setErrorOnInvalidCoupon(false);

        if (!marm_shopgate::getOxConfig()->getConfigParam('iUtfMode')) {
            $cart = $cart->utf8Decode();
        }

        $result = array(
            'currency' => $cart->getCurrency(),
        );

        $oxBasket = $this->basketHelper->buildOxBasket($cart, false, true);

        $oxuser = $oxBasket->getUser();

        if (!empty($oxuser)) {
            $result['customer'] = $this->cartHelper->buildCartCustomer($oxuser);
        }

        try {
            $result['payment_methods']  = $this->getPaymentMethods($cart);
            $result['shipping_methods'] = $this->getShippingMethods($oxBasket);
            $result['external_coupons'] = $this->checkCoupons($oxBasket, $cart);
            $result['items']            = $this->checkItems($cart);
        } catch (ShopgateLibraryException $e) {
            throw $e;
        } catch (Exception $e) {
            $msg = utf8_encode($e->getMessage());
            $msg = 'A(n) ' . get_class($e) . " was thrown while valdiating the cart. Code: " .
                $e->getCode() . ", message: $msg";
            throw new ShopgateLibraryException(ShopgateLibraryException::UNKNOWN_ERROR_CODE, $msg, true);
        }

        try {
            if ($this->userHelper->isGuestAccountCreated() && $oxBasket->getUser()) {
                $oxBasket->getUser()->delete();
            }
            $oxBasket->deleteBasket();
        } catch (Exception $e) {
        }

        return $result;
    }

    /**
     * For non-logged-in users check_cart creates a temporary user account.
     * If check_cart crashes, that account might not get deleted.
     * So we have to clean them up from time to time.
     */
    private function cleanUpCheckCartUsers()
    {
        $name  = shopgate_oxuser::CHECK_CART_USERNAME;
        $sql   = "
			SELECT OXID, OXUSERNAME
			FROM oxuser
			WHERE OXUSERNAME like 'info+%@shopgate.com'
			AND OXLNAME = '$name'
			AND OXCREATE < DATE_SUB(NOW(), INTERVAL 1 MINUTE)
		";
        $users = marm_shopgate::dbGetAll($sql);
        if (empty($users)) {
            return;
        }
        foreach ($users as $user) {
            $id = array_shift($user);
            /** @var oxUser $oxUser */
            $oxUser = oxNew('oxUser');
            if ($oxUser->load($id)) {
                $oxUser->delete();
            }
        }
    }

    /**
     * returns all payment methods that are applicable for the given cart
     *
     * In case the cart is empty it will return an empty array.
     * We shouldn't call getPaymentList and trigger other modules for an empty cart.
     * This can cause unforeseeable issues see OXID-356
     *
     * @param ShopgateCart $cart
     *
     * @return ShopgatePaymentMethod[]
     */
    private function getPaymentMethods(ShopgateCart $cart)
    {
        $result = array();

        if ($this->cartHelper->isShoppingCartEmpty($cart)) {
            return $result;
        }

        $shippingServiceId = $this->shippingHelper->getShippingServiceId($cart);
        if ($shippingServiceId == ShopgateShippingHelper::SHIPPING_SERVICE_ID_MOBILE_SHIPPING) {
            $shippingServiceId = null;
        }
        marm_shopgate::setSessionVar('sShipSet', $shippingServiceId);

        /** @var Payment $paymentController */
        $paymentController = oxNew('Payment');

        /** @var oxPayment[] $paymentMethods */
        $paymentController->init();
        $paymentMethods = $paymentController->getPaymentList();

        if (!is_array($paymentMethods)) {
            return $result;
        }

        foreach ($paymentMethods as $paymentMethod) {
            $method = new ShopgatePaymentMethod();
            $method->setId($paymentMethod->getId());
            $result[] = $method;
        }

        return $result;
    }

    /**
     * @param shopgate_oxbasket $oxBasket
     *
     * @return ShopgateShippingMethod[]
     */
    private function getShippingMethods(shopgate_oxbasket $oxBasket)
    {
        /** @noinspection PhpParamsInspection */
        /** @var oxDeliverySet[] $oxDeliverySets */
        $oxDeliverySets = marm_shopgate::getOxDeliverySetList()->getDeliverySetData(
            null,
            $oxBasket->getUser(),
            $oxBasket
        );
        $oxDeliverySets = $oxDeliverySets[0];

        $shippingMethods = array();
        if (!empty($oxDeliverySets)) {
            foreach ($oxDeliverySets as $oxDeliverySet) {
                marm_shopgate::getOxDeliveryList()->sg_reset();
                $oxBasket->setShipping($oxDeliverySet->oxdeliveryset__oxid->value);
                $oxBasket->calculateBasket(true);

                $amount = $oxBasket->getDelCostNetAsFloat();
                if ($amount === false) {
                    $amount = $oxBasket->getDeliveryCosts();
                }

                $shippingMethod = new ShopgateShippingMethod();
                $shippingMethod->setId($oxDeliverySet->oxdeliveryset__oxid->value);
                $shippingMethod->setTitle($oxDeliverySet->oxdeliveryset__oxtitle->value);
                $shippingMethod->setShippingGroup($oxDeliverySet->oxdeliveryset__shopgate_service_id->value);
                $shippingMethod->setAmount($amount);
                $shippingMethod->setAmountWithTax($oxBasket->getDeliveryCosts());
                $shippingMethod->setTaxPercent($oxBasket->getDelCostVatPercent());
                $shippingMethod->setSortOrder($oxDeliverySet->oxdeliveryset__oxpos->value);

                $shippingMethods[] = $shippingMethod;
            }
        }

        return $shippingMethods;
    }

    public function redeemCoupons(ShopgateCart $cart)
    {
        $this->basketHelper->setErrorOnInvalidCoupon(true);

        $oxBasket = $this->basketHelper->buildOxBasket($cart);

        $result                     = array();
        $result['external_coupons'] = $this->checkCoupons($oxBasket, $cart);

        $this->redeemVouchers($cart);

        $oxBasket->deleteBasket();

        return $result["external_coupons"];
    }

    /**
     * check if the given coupon code is valid for current cart
     *
     * if add coupon is successful it will create a reservation for
     * this coupon and return the reservation id
     *
     * @param shopgate_oxbasket $oxBasket
     * @param ShopgateCart      $cart
     *
     * @throws object
     * @return ShopgateExternalCoupon[]
     */
    protected function checkCoupons(shopgate_oxbasket $oxBasket, ShopgateCart $cart)
    {
        $voucherStack = array();

        $basketVouchers = $oxBasket->getVouchers();
        foreach ($basketVouchers as $simpleVoucher) {
            $key                = strtolower($simpleVoucher->sVoucherNr);
            $voucherStack[$key] = $simpleVoucher;
        }

        $oxBasket->setSkipVouchersChecking(false);
        $oxBasket->calculateBasket(true);

        $voucherErrors = $this->basketHelper->getVoucherErrors();

        $result = array();
        foreach ($cart->getExternalCoupons() as $coupon) {
            $result[] = $this->checkCoupon($coupon, $voucherStack, $voucherErrors);
        }

        return $result;
    }

    /**
     * @param ShopgateExternalCoupon $coupon
     * @param array                  $voucherStack
     * @param string[]               $voucherErrors
     *
     * @return ShopgateExternalCoupon
     */
    private function checkCoupon(ShopgateExternalCoupon $coupon, $voucherStack, $voucherErrors)
    {
        $result = new ShopgateExternalCoupon();

        $key = strtolower($coupon->getCode());

        if (isset($voucherStack[$key])) {
            $simpleVoucher = $voucherStack[$key];

            /** @var oxVoucher $oxVoucher */
            $oxVoucher = oxNew('oxVoucher');
            $oxVoucher->load($simpleVoucher->sVoucherId);

            // Workaround for OXID <= 4.3 (dVoucherdiscount isn't set in those versions)
            if (!isset($simpleVoucher->dVoucherdiscount) && isset($simpleVoucher->fVoucherdiscount)) {
                $simpleVoucher->dVoucherdiscount = floatval(str_replace(',', '.', $simpleVoucher->fVoucherdiscount));
            }

            /** @var oxVoucherSerie $oxVoucherSerie */
            $oxVoucherSerie = $oxVoucher->getSerie();

            $result->setIsValid(true);
            $result->setCode($simpleVoucher->sVoucherNr);
            $result->setName($oxVoucherSerie->oxvoucherseries__oxserienr->value);
            $result->setDescription($oxVoucherSerie->oxvoucherseries__oxseriedescription->value);
            $result->setAmountGross($simpleVoucher->dVoucherdiscount);
            $result->setCurrency($this->getActiveCurrency());
            $result->setIsFreeShipping(false);
            $result->setInternalInfo($this->jsonEncode((array)$simpleVoucher));
        } else {
            $result->setIsValid(false);
            $result->setCode($coupon->getCode());

            if (isset($voucherErrors[$key])) {
                $result->setNotValidMessage($voucherErrors[$key]);
            }
        }

        return $result;
    }

    /**
     * @param ShopgateCartBase $cart
     */
    protected function redeemVouchers(ShopgateCartBase $cart)
    {
        foreach ($cart->getExternalCoupons() as $coupon) {
            $info = $this->jsonDecode($coupon->getInternalInfo(), true);
            if ($info) {
                /** @var oxVoucher $oxVoucher */
                $oxVoucher = oxNew('oxVoucher');
                $oxVoucher->load($info['sVoucherId']);
                $oxVoucher->oxvouchers__oxdateused->setValue(date('Y-m-d', marm_shopgate::getOxUtilsDate()->getTime()));
                $oxVoucher->save();
            }
        }
    }

    /**
     * @param ShopgateCart $cart
     *
     * @return ShopgateCartItem[]
     */
    private function checkItems(ShopgateCart $cart)
    {
        $result = array();
        foreach ($cart->getItems() as $item) {
            $result[] = $this->checkItem($item);
        }

        return $result;
    }

    /**
     * @param ShopgateOrderItem $orderItem
     *
     * @return ShopgateCartItem
     */
    private function checkItem(ShopgateOrderItem $orderItem)
    {
        $cartItem = new ShopgateCartItem();
        $cartItem->setItemNumber($orderItem->getItemNumber());
        $itemNumber = str_replace('parent', '', $orderItem->getItemNumber());

        $id = marm_shopgate::dbGetOne(
            "SELECT oxid FROM oxarticles WHERE {$this->uniqueArticleIdField} = ?",
            array($itemNumber)
        );

        /** @var oxArticle $oxArticle */
        $oxArticle = oxNew('oxArticle');
        if (empty($id) || !$oxArticle->load($id)) {
            $cartItem->setError(ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND);
            $cartItem->setErrorText(
                ShopgateLibraryException::getMessageFor(ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND)
            );

            return $cartItem;
        }

        $minOrderQty   = $this->getMinimumOrderQuantity($oxArticle);
        $maxOrderQty   = $this->getMaximumOrderQuantity($oxArticle);
        $stockQuantity = $oxArticle->oxarticles__oxstock->value;
        $quantity      = $orderItem->getQuantity();
        $isBuyable     =
            $oxArticle->isBuyable()
            && ($quantity <= $stockQuantity || $this->isArticleBuyableWhenOutOfStock($oxArticle))
            && ($quantity >= $minOrderQty)
            && ($quantity <= $maxOrderQty);

        $qtyBuyable = $orderItem->getQuantity();
        if ($quantity > $stockQuantity && !$this->isArticleBuyableWhenOutOfStock($oxArticle)) {
            $qtyBuyable = $stockQuantity;
            $cartItem->setError(ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_NOT_AVAILABLE);
        } elseif ($quantity < $minOrderQty) {
            $qtyBuyable = $minOrderQty;
            $cartItem->setError(ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_UNDER_MINIMUM_QUANTITY);
        } elseif ($quantity > $maxOrderQty) {
            $qtyBuyable = $maxOrderQty;
            $cartItem->setError(ShopgateLibraryException::CART_ITEM_REQUESTED_QUANTITY_OVER_MAXIMUM_QUANTITY);
        }

        $cartItem->setIsBuyable($isBuyable);
        $cartItem->setQtyBuyable($qtyBuyable);
        $cartItem->setStockQuantity($stockQuantity);

        if ($orderItem->getQuantity() > 0) {
            $cartItemExceptions = $this->basketHelper->getCartItemExceptions();
            if (isset($cartItemExceptions[$orderItem->getItemNumber()])) {
                $e = $cartItemExceptions[$orderItem->getItemNumber()];
                $cartItem->setError(ShopgateLibraryException::CART_ITEM_OUT_OF_STOCK);
                $cartItem->setErrorText(utf8_encode($e->getMessage()));
            }
            if (!empty($basketItem)) {
                $basketPrice = $oxArticle->getBasketPrice(
                    $orderItem->getQuantity(),
                    $basketItem->getSelList(),
                    oxNew('oxBasket')
                );
            }
        }

        if (!empty($basketPrice)) {
            $cartItem->setUnitAmount($basketPrice->getNettoPrice());
            $cartItem->setUnitAmountWithTax($basketPrice->getBruttoPrice());
        } else {
            $cartItem->setUnitAmount($this->formatPriceNumber($oxArticle->getPrice()->getNettoPrice()));
            $cartItem->setUnitAmountWithTax($this->formatPriceNumber($oxArticle->getPrice()->getBruttoPrice()));
        }

        /*
         * TODO validate options
        $oxidOptions = $this->getOptionsForArticle($oxArticle);
        foreach($orderItem->getOptions() as $option) {
            $selectionFound = false;
            if (isset($oxidOptions[$option->getOptionNumber()])) {
                $oxidOption = $oxidOptions[$option->getOptionNumber()];
                if (isset($oxidOption['values'][$option->getValueNumber()])) {
                    $selectionFound = true;
                }
            }
            if (!$selectionFound) {
                $errorCode = ShopgateLibraryException::CART_ITEM_REQUESTED_SELECTION_NOT_FOUND;
            }
        }
        */
        $cartItem->setOptions($orderItem->getOptions());
        $cartItem->setInputs($orderItem->getInputs());
        $cartItem->setAttributes($orderItem->getAttributes());

        if ($cartItem->getError() && !$cartItem->getErrorText()) {
            $cartItem->setErrorText(ShopgateLibraryException::getMessageFor($cartItem->getError()));
        }

        return $cartItem;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return bool
     */
    private function isArticleBuyableWhenOutOfStock(oxArticle $oxArticle)
    {
        return in_array($oxArticle->oxarticles__oxstockflag->value, array(1, 4));
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return int
     */
    private function getMinimumOrderQuantity(oxArticle $oxArticle)
    {
        $result = 0;
        if (!empty($oxArticle->oxarticles__d3oqm_minimum->value)) {
            // Plugin "Bestellmengenmanager"
            $result = $oxArticle->oxarticles__d3oqm_minimum->value;
            if (!empty($oxArticle->oxarticles__d3oqm_package->value)) {
                $result /= $oxArticle->oxarticles__d3oqm_package->value;
            }
        }

        return $result;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return int
     */
    private function getMaximumOrderQuantity(oxArticle $oxArticle)
    {
        $result = 1000000000;
        if (!empty($oxArticle->oxarticles__d3oqm_maximum->value)) {
            // Plugin "Bestellmengenmanager"
            $result = $oxArticle->oxarticles__d3oqm_maximum->value;
            if (!empty($oxArticle->oxarticles__d3oqm_package->value)) {
                $result /= $oxArticle->oxarticles__d3oqm_package->value;
            }
        }

        return $result;
    }

    ###################################################################################################################
    ## Settings Export
    ###################################################################################################################

    public function getSettings()
    {
        // Oxid does not support tax classes and rules
        // you can define a global tax rate and override it on each article
        // and you can specify for each country that tax is free or not free

        $helper = new ShopgateSettingsExportHelper($this->config);

        return array(
            'allowed_address_countries'  => $helper->getAllowedAddressCountries(),
            'allowed_shipping_countries' => $helper->getAllowedShippingCountries(),
            'customer_groups'            => $helper->getCustomerGroups(),
            'tax'                        => $helper->getTaxSettings(),
            'payment_methods'            => $helper->getPaymentMethods(),
        );
    }

    ###################################################################################################################
    ## Register customer
    ###################################################################################################################

    public function registerCustomer($user, $pass, ShopgateCustomer $customer)
    {
        if (!marm_shopgate::getOxConfig()->getConfigParam('iUtfMode')) {
            /** @var ShopgateCustomer $customer */
            $customer = $customer->utf8Decode();
        }

        /** @var oxUser $oxUser */
        $oxUser = oxNew('oxUser');

        $exists = $oxUser->checkIfEmailExists($user);
        if ($exists) {
            throw new ShopgateLibraryException(ShopgateLibraryException::REGISTER_USER_ALREADY_EXISTS);
        }

        $invoiceAddress = null;
        foreach ($customer->getAddresses() as $address) {
            if ($address->getIsInvoiceAddress()) {
                $invoiceAddress = $address;
            }
        }
        if (empty($invoiceAddress)) {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                'invoice address missing',
                true
            );
        }

        $oxUser->setPassword($pass);
        $oxUser->oxuser__oxactive    = new oxField(1, oxField::T_RAW);
        $oxUser->oxuser__oxshopid    = new oxField(marm_shopgate::getOxConfig()->getShopId(), oxField::T_RAW);
        $oxUser->oxuser__oxusername  = new oxField($user, oxField::T_RAW);
        $oxUser->oxuser__oxcompany   = new oxField($invoiceAddress->getCompany(), oxField::T_RAW);
        $oxUser->oxuser__oxfname     = new oxField($invoiceAddress->getFirstName(), oxField::T_RAW);
        $oxUser->oxuser__oxlname     = new oxField($invoiceAddress->getLastName(), oxField::T_RAW);
        $oxUser->oxuser__oxstreet    = new oxField($invoiceAddress->getStreetName1(), oxField::T_RAW);
        $oxUser->oxuser__oxstreetnr  = new oxField($invoiceAddress->getStreetNumber1(), oxField::T_RAW);
        $oxUser->oxuser__oxaddinfo   = new oxField($invoiceAddress->getStreet2(), oxField::T_RAW);
        $oxUser->oxuser__oxcity      = new oxField($invoiceAddress->getCity(), oxField::T_RAW);
        $oxUser->oxuser__oxcountryid = new oxField(
            $this->userHelper->getCountryOxid($invoiceAddress->getCountry()),
            oxField::T_RAW
        );
        $oxUser->oxuser__oxstateid   = new oxField(
            $this->userHelper->getStateOxid($invoiceAddress->getState()),
            oxField::T_RAW
        );
        $oxUser->oxuser__oxzip       = new oxField($invoiceAddress->getZipcode(), oxField::T_RAW);
        $oxUser->oxuser__oxfon       = new oxField($invoiceAddress->getPhone(), oxField::T_RAW);
        $oxUser->oxuser__oxmobfon    = new oxField($invoiceAddress->getMobile(), oxField::T_RAW);
        $oxUser->oxuser__oxbirthdate = new oxField($invoiceAddress->getBirthday(), oxField::T_RAW);
        $oxUser->oxuser__oxsal       = new oxField(
            $this->userHelper->getOxidGender($invoiceAddress->getGender()),
            oxField::T_RAW
        );

        if (!$oxUser->save()) {
            throw new ShopgateLibraryException(ShopgateLibraryException::REGISTER_FAILED_TO_ADD_USER);
        }

        $shopgate_oxuser = new shopgate_oxuser();
        $shopgate_oxuser->load($oxUser->getId());
        $shopgate_oxuser->setAutoGroups($oxUser->oxuser__oxcountryid);
        $shopgate_oxuser->addToGroup('oxidnotyetordered');

        foreach ($customer->getAddresses() as $address) {
            /** @var oxAddress $oxAddress */
            $compareFields = array(
                'company',
                'firstName',
                'lastName',
                'street1',
                'street2',
                'city',
                'country',
                'state',
                'zipcode',
                'phone',
            );
            if ($address->getIsDeliveryAddress() && !$address->compare($invoiceAddress, $address, $compareFields)) {
                $oxAddress                         = oxNew('oxAddress');
                $oxAddress->oxaddress__oxuserid    = new oxField($oxUser->getId(), oxField::T_RAW);
                $oxAddress->oxaddress__oxcompany   = new oxField($address->getCompany(), oxField::T_RAW);
                $oxAddress->oxaddress__oxfname     = new oxField($address->getFirstName(), oxField::T_RAW);
                $oxAddress->oxaddress__oxlname     = new oxField($address->getLastName(), oxField::T_RAW);
                $oxAddress->oxaddress__oxstreet    = new oxField($address->getStreetName1(), oxField::T_RAW);
                $oxAddress->oxaddress__oxstreetnr  = new oxField($address->getStreetNumber1(), oxField::T_RAW);
                $oxAddress->oxaddress__oxaddinfo   = new oxField($address->getStreet2(), oxField::T_RAW);
                $oxAddress->oxaddress__oxcity      = new oxField($address->getCity(), oxField::T_RAW);
                $oxAddress->oxaddress__oxcountryid = new oxField(
                    $this->userHelper->getCountryOxid($address->getCountry()), oxField::T_RAW
                );
                $oxAddress->oxaddress__oxstateid   = new oxField(
                    $this->userHelper->getStateOxid($address->getState()),
                    oxField::T_RAW
                );
                $oxAddress->oxaddress__oxzip       = new oxField($address->getZipcode(), oxField::T_RAW);
                $oxAddress->oxaddress__oxfon       = new oxField($address->getPhone(), oxField::T_RAW);
                $oxAddress->oxaddress__oxsal       = new oxField(
                    $this->userHelper->getOxidGender($address->getGender()), oxField::T_RAW
                );
                $oxAddress->save();
            }
        }
    }

    private function checkPluginStatus()
    {
        if (class_exists('oxModule')) {
            /** @var oxModule $oxModule */
            $oxModule = oxNew('oxModule');
            $oxModule->load('shopgate');
            if (!$oxModule->isActive()) {
                throw new ShopgateLibraryException(ShopgateLibraryException::CONFIG_PLUGIN_NOT_ACTIVE);
            }
        }
    }

    public function checkStock(ShopgateCart $cart)
    {
        if (!marm_shopgate::getOxConfig()->getConfigParam('iUtfMode')) {
            $cart = $cart->utf8Decode();
        }

        return $this->checkItems($cart);
    }

    public function createMediaCsv()
    {
        # TODO implement
    }

    public function syncFavouriteList($customerToken, $items)
    {
        # Won't be implemented since Oxid doesn't have wishlists
    }

    public function log($msg, $type = ShopgateLogger::LOGTYPE_ERROR)
    {
        if (!empty($_REQUEST['trace_id'])) {
            $msg = "[{$_REQUEST['trace_id']}] $msg";
        }
        parent::log($msg, $type);
    }
}
