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

require_once dirname(__FILE__) . '/shopgate_install_helper.php';

class marm_shopgate_oxoutput extends marm_shopgate_oxoutput_parent
{
    /** @var ShopgateConfigOxid */
    private $config;

    /**
     * returns $sValue filtered by parent and marm_shopgate_oxoutput::marmReplaceBody
     *
     * @param string $sValue
     * @param string $sClassName
     *
     * @return mixed
     * @see oxOutput::process
     */
    public function process($sValue, $sClassName)
    {
        $sValue = parent::process($sValue, $sClassName);
        if (!$this->isAjax() && $sClassName != 'oxemail') {
            if (!isAdmin()) {
                $sValue = $this->makeMobile($sValue);
            } else {
                $version = marm_shopgate::getOxConfig()->getVersion();
                if (
                    (version_compare($version, '4.6.0', '<') && $sClassName == 'shop_system')
                    || (version_compare($version, '4.7.0', '<') && $sClassName == 'module')
                ) {
                    $helper = new ShopgateInstallHelper();
                    $helper->install();
                }
            }
        }

        return $sValue;
    }

    /**
     * Is the current request is a Ajax-Request?
     *
     * @return boolean
     */
    protected function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest") {
            return true;
        }

        return false;
    }

    protected function makeMobile($sValue)
    {
        marm_shopgate::getInstance()->init();
        $this->config = marm_shopgate::getInstance()->getConfig();

        $sMobileSnippet = $this->makeMobileRedirect();
        if ($sMobileSnippet) {
            $sValue = str_ireplace("</head>", "\n{$sMobileSnippet}\n</head>", $sValue);
        }

        return $sValue;
    }

    protected function getGrantRedirect(oxView $oActiveView)
    {
        /** @noinspection PhpUndefinedClassInspection */
        if (class_exists('oxWidget') && $oActiveView instanceof oxWidget) {
            // oxWidgets are small containers/boxes at the site
            return false;
        }

        $selectedLang = marm_shopgate::getOxLang()->getLanguageAbbr();

        $validLanguages = trim($this->config->getLanguages());
        if (!empty($validLanguages)) {
            $validLanguages = preg_split("/\s*,\s*/", $validLanguages);
        }

        $grantRedirect = !isAdmin() && (empty($validLanguages) || in_array($selectedLang, $validLanguages));

        return $grantRedirect;
    }

    /**
     * Will make a redirect to the mobile webpage if called with a smartphone
     *
     * if redirect is not allowed, the function will return the HTML-Snippet
     * to show Mobile WebPage Toggle-Button on Top of the page
     *
     * @return string
     */
    protected function makeMobileRedirect()
    {
        /** @var oxView $oActiveView */
        $oActiveView = marm_shopgate::getOxConfig()->getActiveView();
        $viewClass   = strtolower($oActiveView->getClassName());

        /** @var ShopgateBuilder $builder */
        $builder = oxNew("ShopgateBuilder", $this->config); // must be initialized BEFORE getting logger instance

        if (!empty($_REQUEST['sg_debug'])) {
            ShopgateLogger::getInstance()->enableDebug();
            ShopgateLogger::getInstance()->log(
                __FUNCTION__ . ": URI={$_SERVER['REQUEST_URI']}, viewClass=$viewClass",
                ShopgateLogger::LOGTYPE_DEBUG
            );
        }

        if (!$this->getGrantRedirect($oActiveView)) {
            return '';
        }

        $oShopgateRedirect = $builder->buildRedirect();

        $autoRedirect = $this->config->getRedirectType() == "header";

        // index
        if ($viewClass == 'start') {
            return $oShopgateRedirect->buildScriptShop($autoRedirect);
        }
        // product
        if ($viewClass == 'details') {
            /** @var Details $oActiveView */
            $oArticle = $oActiveView->getProduct();
            if (!$oArticle->oxarticles__marm_shopgate_export->value) {
                return '';
            }
            $sObjId = $oArticle->{"oxarticles__{$this->config->getArticleIdentifier()}"}->value;
            if (!$sObjId) {
                $sObjId = $oArticle->oxarticles__oxid->value;
            }
            if (
                !$oArticle->getParentArticle() && !$oArticle->sg_act_as_child
                && $this->config->isVariantParentBuyable()
            ) {
                $sObjId = "parent$sObjId";
            }

            return $oShopgateRedirect->buildScriptItem($sObjId, $autoRedirect);
        }
        // category
        if (in_array($viewClass, array('alist', 'fcfatsearch_productlist'))) {
            $sObjId = $oActiveView->getCategoryId();

            if (!$oActiveView->getActCategory()->getIsVisible()) {
                // category is hidden
                return $oShopgateRedirect->buildScriptDefault($autoRedirect);
            }

            return $oShopgateRedirect->buildScriptCategory($sObjId, $autoRedirect);
        }
        // brand
        if ($viewClass == 'manufacturerlist') {
            /** @var ManufacturerList $oActiveView */
            $manufacturer = $oActiveView->getActManufacturer();
            if (empty($manufacturer) || !$manufacturer->getId()) {
                return '';
            }
            $sObjId = $manufacturer->oxmanufacturers__oxtitle->value;

            return $oShopgateRedirect->buildScriptBrand($sObjId, $autoRedirect);
        }
        // search
        if ($viewClass == 'search') {
            /** @var Search $oActiveView */
            return $oShopgateRedirect->buildScriptSearch($oActiveView->getSearchParam());
        }
        if ($viewClass == 'content') {
            // redirect to a cms-page if the page exist at shopgate with the same Url key
            $sObjId = $oActiveView->getContent()->oxcontents__oxid->value;
            $shopId = marm_shopgate::getOxConfig()->getShopId();
            $languageId = marm_shopgate::getOxLang()->getBaseLanguage();

            $seoUrl = ShopgateRedirectHelper::getSeoUrl($sObjId, $shopId, $languageId);

            return $oShopgateRedirect->buildScriptCms($seoUrl, $autoRedirect);
        }

        return $oShopgateRedirect->buildScriptDefault($autoRedirect);
    }
}
