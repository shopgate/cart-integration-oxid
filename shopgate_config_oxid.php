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
class ShopgateConfigOxid extends ShopgateConfig
{
    const ARTICLE_IDENTIFIER_OXID     = 'oxid';
    const ARTICLE_IDENTIFIER_OXARTNUM = 'oxartnum';
    const ARTICLE_NAME_EXPORT_TYPE_NAME      = 'name';
    const ARTICLE_NAME_EXPORT_TYPE_SHORTDESC = 'shortdesc';
    const ARTICLE_NAME_EXPORT_TYPE_BOTH      = 'both';
    const OXID_VARTYPE_ARRAY   = 'arr';
    const OXID_VARTYPE_BOOLEAN = 'bool';
    const OXID_VARTYPE_STRING  = 'str';

    protected $languages = '';

    /** @var bool */
    protected $unblocked_orders_as_paid = false;

    /** @var string */
    protected $orderfolder_blocked = 'ORDERFOLDER_PROBLEMS';

    /** @var string */
    protected $orderfolder_unblocked = 'ORDERFOLDER_NEW';

    /** @var string */
    protected $redirect_type = 'header';

    /** @var bool */
    protected $send_mails = false;

    /** @var bool */
    protected $send_mails_to_owner = false;

    /** @var string */
    protected $article_identifier = self::ARTICLE_IDENTIFIER_OXID;

    /** @var string */
    protected $article_name_export_type = self::ARTICLE_NAME_EXPORT_TYPE_NAME;

    /** @var string */
    protected $variant_parent_buyable = 'oxid';

    /** @var string */
    protected $htaccess_user;

    /** @var string */
    protected $htaccess_password;

    /** @var bool */
    protected $suppress_order_notes = false;

    /** @var bool */
    protected $sys_use_stock;

    /** @var bool */
    protected $sys_stock_on_default_message;

    /** @var bool */
    protected $sys_stock_off_default_message;

    /** @var double */
    protected $sys_default_vat;

    /** @var ShopgateUnknownOxidConfigFields */
    protected $unknownOxidConfigFields;

    public function startup()
    {
        $this->setPluginName('oxid');
        $this->unknownOxidConfigFields = new ShopgateUnknownOxidConfigFields(
            $this,
            marm_shopgate::getOxConfig(),
            marm_shopgate::getInstance()
        );

        $this->setEnablePing(true);
        $this->setEnableCron(true);

        $this->setEnableGetCustomer(true);
        $this->setEnableRegisterCustomer(true);

        $this->setEnableRedirectKeywordUpdate(true);
        $this->setEnableGetSettings(true);

        $this->setEnableGetItemsCsv(true);
        $this->setEnableGetCategoriesCsv(true);
        $this->setEnableGetReviewsCsv(true);

        $this->setEnableGetItems(true);
        $this->setEnableGetCategories(true);
        $this->setEnableGetReviews(true);

        $this->setEnableGetLogFile(true);
        $this->setEnableClearLogFile(true);
        $this->setEnableClearCache(true);

        $this->setEnableCheckCart(true);
        $this->setEnableRedeemCoupons(true);

        $this->setEnableAddOrder(true);
        $this->setEnableUpdateOrder(true);
        $this->setEnableGetOrders(true);

        $this->setEnableCheckStock(true);

        $this->supported_fields_check_cart   = array('external_coupons', 'shipping_methods');
        $this->supported_fields_get_settings = array('tax');

        $iUtfMode = marm_shopgate::getOxConfig()->getConfigParam('iUtfMode');
        if ($iUtfMode == 1 || (stripos($iUtfMode, 'utf') !== false)) {
            $this->setEncoding('UTF-8');
        } else {
            $this->setEncoding('ISO-8859-15');
        }

        $this->loadFromDatabase();

        return true;
    }

    public function load(array $settings = null)
    {
        $this->loadFromDatabase();
        $this->loadArray($settings);
    }

    public function loadFromDatabase()
    {
        $oxConfig = marm_shopgate::getOxConfig();

        $result = array();
        foreach (marm_shopgate::getInstance()->_getConfig() as $sConfigKey => $aOptions) {
            $sValue = $oxConfig->getConfigParam(marm_shopgate::getInstance()->getOxidConfigKey($sConfigKey));
            if ($sValue !== null) {
                $result[$sConfigKey] = $sValue;
            }
        }

        $shopDir            = marm_shopgate::getOxConfig()->getConfigParam('sShopDir');
        $shopgatePathSuffix = "shopgate" . DS; # TODO . $aConfig["shop_number"] . DS;

        $exportPath = $shopDir . 'export' . DS . $shopgatePathSuffix;
        $logPath    = $shopDir . 'log' . DS . $shopgatePathSuffix;
        $tmpPath    = $shopDir . 'tmp' . DS . $shopgatePathSuffix;

        if (version_compare(marm_shopgate::getOxConfig()->getVersion(), '4.2.0', '<')) {
            # log folder doesn't exist in Oxid 4.1
            $logPath = $tmpPath . 'log';
        }

        if (!file_exists($exportPath)) {
            @mkdir($exportPath, 0777, true);
        }
        if (!file_exists($logPath)) {
            @mkdir($logPath, 0777, true);
        }
        if (!file_exists($tmpPath)) {
            @mkdir($tmpPath, 0777, true);
        }

        $result['export_folder_path'] = $exportPath;
        $result['log_folder_path']    = $logPath;
        $result['cache_folder_path']  = $tmpPath;

        // read Mobile Header special settings
        $mobileHeaderParent  = $oxConfig->getConfigParam(
            marm_shopgate::getInstance()->getOxidConfigKey('mobile_header_parent')
        );
        $mobileHeaderPrepend = $oxConfig->getConfigParam(
            marm_shopgate::getInstance()->getOxidConfigKey('mobile_header_prepend')
        );

        if ($mobileHeaderParent !== null) {
            $result['mobile_header_parent'] = $mobileHeaderParent;
        }

        if ($mobileHeaderPrepend !== null) {
            $result['mobile_header_prepend'] = $mobileHeaderPrepend;
        }

        $configMapping = array(
            'sys_use_stock'                 => 'blUseStock',
            'sys_stock_on_default_message'  => 'blStockOnDefaultMessage',
            'sys_stock_off_default_message' => 'blStockOffDefaultMessage',
            'sys_default_vat'               => 'dDefaultVAT',
        );

        foreach ($configMapping as $sgConfigField => $oxConfigField) {
            $result[$sgConfigField] = $oxConfig->getConfigParam($oxConfigField);
        }

        $this->loadArray($result);

        $this->unknownOxidConfigFields->load();
    }

    public function save(array $fieldList, $validate = true)
    {
        $oxConfig     = marm_shopgate::getOxConfig();
        $configFields = marm_shopgate::getInstance()->_getConfig();

        $unknownOxidConfigurationFields = array();
        foreach ($fieldList as $field) {
            if (!isset($configFields[$field])) {
                $unknownOxidConfigurationFields[$field] = $this->{$field};
                continue;
            }

            $type     = $this->getOxidType($this->{$field}, $configFields[$field]);
            $oxidName = marm_shopgate::getInstance()->getOxidConfigKey($field);
            $oxConfig->saveShopConfVar($type, $oxidName, $this->{$field});
        }

        $this->unknownOxidConfigFields->save($unknownOxidConfigurationFields);
    }

    /**
     * @param mixed $value
     * @param array $confVar
     *
     * @return string
     */
    private function getOxidType($value, $confVar)
    {
        if (is_array($value)) {
            return self::OXID_VARTYPE_ARRAY;
        }
        switch ($confVar['type']) {
            case 'select':
                return self::OXID_VARTYPE_STRING;
            case 'input':
                return self::OXID_VARTYPE_STRING;
            case 'checkbox':
                return self::OXID_VARTYPE_BOOLEAN;
        }

        return self::OXID_VARTYPE_STRING;
    }

    public function setLanguages($value)
    {
        $this->languages = $value;
    }

    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * @return bool
     */
    public function getUnblockedOrdersAsPaid()
    {
        return $this->unblocked_orders_as_paid;
    }

    /**
     * @param bool $value
     */
    public function setUnblockedOrdersAsPaid($value)
    {
        $this->unblocked_orders_as_paid = $value;
    }

    /**
     * @return string
     */
    public function getOrderfolderBlocked()
    {
        return $this->orderfolder_blocked;
    }

    /**
     * @param string $value
     */
    public function setOrderfolderBlocked($value)
    {
        $this->orderfolder_blocked = $value;
    }

    /**
     * @return string
     */
    public function getOrderfolderUnblocked()
    {
        return $this->orderfolder_unblocked;
    }

    /**
     * @param string $value
     */
    public function setOrderfolderUnblocked($value)
    {
        $this->orderfolder_unblocked = $value;
    }

    /**
     * @return string
     */
    public function getRedirectType()
    {
        return $this->redirect_type;
    }

    /**
     * @param string $value
     */
    public function setRedirectType($value)
    {
        $this->redirect_type = $value;
    }

    /**
     * @return bool
     */
    public function getSysUseStock()
    {
        return (bool)$this->sys_use_stock;
    }

    /**
     * @param bool $value
     */
    public function setSysUseStock($value)
    {
        $this->sys_use_stock = $value;
    }

    /**
     * @return bool
     */
    public function getSysStockOnDefaultMessage()
    {
        return (bool)$this->sys_stock_on_default_message;
    }

    /**
     * @param bool $value
     */
    public function setSysStockOnDefaultMessage($value)
    {
        $this->sys_stock_on_default_message = $value;
    }

    /**
     * @return bool
     */
    public function getSysStockOffDefaultMessage()
    {
        return (bool)$this->sys_stock_off_default_message;
    }

    /**
     * @param bool $value
     */
    public function setSysStockOffDefaultMessage($value)
    {
        $this->sys_stock_off_default_message = $value;
    }

    /**
     * @return float
     */
    public function getSysDefaultVat()
    {
        return $this->sys_default_vat;
    }

    /**
     * @param float $value
     */
    public function setSysDefaultVat($value)
    {
        $this->sys_default_vat = $value;
    }

    /**
     * @return boolean
     */
    public function getSendMails()
    {
        return $this->send_mails;
    }

    /**
     * @param boolean
     */
    public function setSendMails($value)
    {
        $this->send_mails = $value;
    }

    /**
     * @return boolean
     */
    public function getSendMailsToOwner()
    {
        return $this->send_mails_to_owner;
    }

    /**
     * @param boolean
     */
    public function setSendMailsToOwner($value)
    {
        $this->send_mails_to_owner = $value;
    }

    /**
     * @return string
     */
    public function getArticleIdentifier()
    {
        return $this->article_identifier;
    }

    /**
     * @param string $value
     */
    public function setArticleIdentifier($value)
    {
        $this->article_identifier = $value;
    }

    /**
     * @return string
     */
    public function getArticleNameExportType()
    {
        return $this->article_name_export_type;
    }

    /**
     * @param string $value
     */
    public function setArticleNameExportType($value)
    {
        $this->article_name_export_type = $value;
    }

    /**
     * @return string
     */
    public function getVariantParentBuyable()
    {
        return $this->variant_parent_buyable;
    }

    /**
     * @return bool
     */
    public function isVariantParentBuyable()
    {
        if ($this->variant_parent_buyable == 'oxid') {
            return marm_shopgate::getOxConfig()->getConfigParam('blVariantParentBuyable');
        }

        return ($this->variant_parent_buyable === 'true');
    }

    /**
     * @param string $value
     */
    public function setVariantParentBuyable($value)
    {
        $this->variant_parent_buyable = $value;
    }

    /**
     * @return string
     */
    public function getHtaccessUser()
    {
        return $this->htaccess_user;
    }

    /**
     * @param string $value
     */
    public function setHtaccessUser($value)
    {
        $this->htaccess_user = $value;
    }

    /**
     * @return string
     */
    public function getHtaccessPassword()
    {
        return $this->htaccess_password;
    }

    /**
     * @param string $value
     */
    public function setHtaccessPassword($value)
    {
        $this->htaccess_password = $value;
    }

    /**
     * @return bool
     */
    public function getSuppressOrderNotes()
    {
        return $this->suppress_order_notes;
    }

    /**
     * @param bool $value
     */
    public function setSuppressOrderNotes($value)
    {
        $this->suppress_order_notes = $value;
    }
}
