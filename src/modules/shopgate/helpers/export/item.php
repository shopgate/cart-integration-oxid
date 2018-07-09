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
class ShopgateItemExportHelper
{
    /**
     * Internal stock flag statuses
     */
    const DELIVERY_STATUS_STANDARD      = 1;
    const DELIVERY_STATUS_OFFLINE       = 2;
    const DELIVERY_STATUS_NOT_ORDERABLE = 3;
    const DELIVERY_STATUS_STOREHOUSE    = 4;

    /** @var array */
    public $itemInCategorySortCache;

    /** @var array */
    public $highlightItemsCache;

    /** @var array */
    public $manufacturersCache;

    /** @var ShopgateConfigOxid */
    private $config;

    /** @var marm_shopgate */
    private $marmShopgate;

    /** @var array */
    private $unitMultiplicators = array(
        'ml' => 100,
        'g'  => 100,
    );

    /**
     * @param ShopgateConfigOxid $config
     * @param marm_shopgate      $marm_shopgate $marm_shopgate
     */
    public function __construct(ShopgateConfigOxid $config, marm_shopgate $marm_shopgate)
    {
        $this->config       = $config;
        $this->marmShopgate = $marm_shopgate;
    }

    /**
     * @param array $articleIds
     */
    public function init(array $articleIds)
    {
        $this->initItemInCategorySortCache($articleIds);
        $this->initHighlightItemsCache();
        $this->initManufacturersCache();
    }

    /**
     * @param array $articleIds
     */
    public function initItemInCategorySortCache(array $articleIds)
    {
        $this->itemInCategorySortCache = array();

        /** @var ADORecordSet_empty $rs */
        $rs     = $this->marmShopgate->dbGetOne("SELECT MAX(oxpos) AS max FROM oxobject2category");
        $maxPos = !empty($rs)
            ? (int)$rs->fields['max']
            : 1000000;

        /** @var oxcategory $oxCategory */
        $oxCategory = oxNew('oxcategory');
        $select     = "
			SELECT DISTINCT
			oc.oxcatnid,
			oc.oxpos,
			oc.oxobjectid
			FROM `{$oxCategory->getViewName()}` c
			JOIN `oxobject2category` oc ON (c.oxid = oc.oxcatnid)
			WHERE oc.oxobjectid IN ('" . implode("', '", $articleIds) . "')
			  AND c.oxdefsort = ''";
        $results = $this->marmShopgate->dbGetAll($select);
        foreach ($results as $rs) {
            $this->itemInCategorySortCache[$rs['oxcatnid']][$rs['oxobjectid']] = $maxPos - $rs['oxpos'];
        }
    }

    public function initHighlightItemsCache()
    {
        $this->highlightItemsCache = array();
        $shopID                    = $this->marmShopgate->getOxConfig()->getShopId();
        $select                    = "
			SELECT a2a.oxartid, COUNT(a2a.oxartid) as cnt
			FROM oxactions2article a2a
			JOIN oxactions a ON a.oxid = a2a.oxactionid
			WHERE a2a.oxshopid = '{$shopID}'
			AND a.shopgate_is_highlight = 1
			GROUP BY a2a.oxartid
			HAVING cnt > 0";
        $results = $this->marmShopgate->dbGetAll($select);
        foreach ($results as $rs) {
            $this->highlightItemsCache[] = $rs['oxartid'];
        }
    }

    public function initManufacturersCache()
    {
        $this->manufacturersCache = array();
        $sManufacturersTable      = getViewName('oxmanufacturers');
        $sLangTag                 = $this->getLanguageTagForTable($sManufacturersTable);

        $manufacturers = $this->marmShopgate->dbGetAll(
            "SELECT OXID, OXTITLE{$sLangTag} as OXTITLE FROM {$sManufacturersTable}"
        );
        foreach ($manufacturers as $manufacturer) {
            $this->manufacturersCache[$manufacturer['OXID']] = $manufacturer['OXTITLE'];
        }
    }

    /**
     * function to get language tag in oxid versions 4.0 - 4.5 formats
     * in 4.5 introduced new views for each language with language abbr in ending.
     * example: oxv_oxarticles_en, oxv_oxcategories_de
     *
     * @param string $tableName
     *
     * @return string
     */
    private function getLanguageTagForTable($tableName)
    {
        $langTag  = $this->marmShopgate->getOxLang()->getLanguageTag();
        $langAbbr = $this->marmShopgate->getOxLang()->getLanguageAbbr();
        if (strpos($tableName, 'oxv_') !== false && strpos($tableName, '_' . $langAbbr) !== false) {
            $langTag = '';
        }

        return $langTag;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return int
     */
    public function getVpe(oxArticle $oxArticle)
    {
        $vpe = 1;
        if (isset($oxArticle->oxarticles__oxvpe)) {
            // Oxid EE
            $vpe = $oxArticle->oxarticles__oxvpe->value;
        } elseif (isset($oxArticle->oxarticles__d3oqm_package->value)) {
            // Plugin "Bestellmengenmanager"
            $vpe = $oxArticle->oxarticles__d3oqm_package->value;
        }

        return max($vpe, 1);
    }

    /**
     * @example 179.99
     *
     * @param oxArticle $oxArticle
     *
     * @return float|int|string
     */
    public function getUnitAmount(oxArticle $oxArticle)
    {
        $result = $oxArticle->getPrice()->getBruttoPrice();

        return $this->formatPriceNumber($result * $this->getVpe($oxArticle));
    }

    /**
     * @example 159.99
     *
     * @param oxArticle $oxArticle
     *
     * @return float|int|string
     */
    public function getUnitAmountNet(oxArticle $oxArticle)
    {
        $price = $oxArticle->getPrice();

        $salePrice = $this->marmShopgate->getOxUtils()->getConfig()->getConfigParam('blEnterNetPrice')
            ? $price->getNettoPrice()
            : oxPrice::brutto2Netto($price->getBruttoPrice(), $oxArticle->getArticleVat());

        return $this->formatPriceNumber($salePrice * $this->getVpe($oxArticle), 4);
    }

    /**
     * @example 199.99
     *
     * @param oxArticle $oxArticle
     *
     * @return float|string
     */
    public function getOldUnitAmount(oxArticle $oxArticle)
    {
        if ($oxArticle->getPrice()->getBruttoPrice() != $oxArticle->getBasePrice()) {
            return $this->formatPriceNumber($oxArticle->getBasePrice() * $this->getVpe($oxArticle));
        }

        return '';
    }

    /**
     * @example 169.99
     *
     * @param oxArticle $oxArticle
     *
     * @return float|string
     */
    public function getOldUnitAmountNet(oxArticle $oxArticle)
    {
        $price        = $oxArticle->getPrice();
        $priceModeNet = $this->marmShopgate->getOxUtils()->getConfig()->getConfigParam('blEnterNetPrice');

        $basePrice = $priceModeNet
            ? $oxArticle->getBasePrice()
            : oxPrice::brutto2Netto($oxArticle->getBasePrice(), $oxArticle->getArticleVat());

        $salePrice = $priceModeNet
            ? $price->getNettoPrice()
            : oxPrice::brutto2Netto($price->getBruttoPrice(), $oxArticle->getArticleVat());

        if ($salePrice != $basePrice) {
            return $this->formatPriceNumber($basePrice * $this->getVpe($oxArticle), 4);
        }

        return '';
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return int
     */
    public function getMaximumOrderQuantity(oxArticle $oxArticle)
    {
        $result = 0;
        if (!empty($oxArticle->oxarticles__d3oqm_maximum->value)) {
            // Plugin "Bestellmengenmanager"
            $result = $oxArticle->oxarticles__d3oqm_maximum->value;
            if (!empty($oxArticle->oxarticles__d3oqm_package->value)) {
                $result /= $oxArticle->oxarticles__d3oqm_package->value;
            }
        }

        return floor($result / $this->getVpe($oxArticle));
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return int
     */
    public function getMinimumOrderQuantity(oxArticle $oxArticle)
    {
        $result = 0;
        if (!empty($oxArticle->oxarticles__d3oqm_minimum->value)) {
            // Plugin "Bestellmengenmanager"
            $result = $oxArticle->oxarticles__d3oqm_minimum->value;
            if (!empty($oxArticle->oxarticles__d3oqm_package->value)) {
                $result /= $oxArticle->oxarticles__d3oqm_package->value;
            }
        }

        return ceil($result / $this->getVpe($oxArticle));
    }

    /**
     * Loads delivery time in text format for article
     *
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getAvailabilityText(oxArticle $oxArticle)
    {
        if ($oxArticle->oxarticles__oxstock->value > 0 || !$this->getUseStock($oxArticle)) {
            $stockText = $this->getInStockMessage($oxArticle);
        } else {
            $stockText = $this->getOutOfStockMessage($oxArticle);
            $stockText .= $this->getAvailableOnMessage($oxArticle);
        }

        if ($deliveryTimeText = $this->getArticleDeliveryTime($oxArticle)) {
            $stockText .= ' | ' . $deliveryTimeText;
        }

        return $stockText;
    }

    /**
     * Retrieve out of stock message from product page or
     * default to constant
     *
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    private function getOutOfStockMessage(oxArticle $oxArticle)
    {
        $stockMessage = $oxArticle->oxarticles__oxnostocktext->value;

        if (empty($stockMessage) && $this->config->getSysStockOffDefaultMessage()) {
            $stockMessage = $this->getTranslation('MESSAGE_NOT_ON_STOCK', array('DETAILS_NOTONSTOCK'));
        }

        return $stockMessage;
    }

    /**
     * Retrieve in stock message from product page or
     * default to constant
     *
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    private function getInStockMessage(oxArticle $oxArticle)
    {
        $lang      = $this->marmShopgate->getOxLang()->getLanguageAbbr();
        $stockText = $oxArticle->oxarticles__oxstocktext->value;

        if (empty($stockText) && $this->config->getSysStockOnDefaultMessage()) {
            $stockText = $this->getTranslation(
                'READY_FOR_SHIPPING',
                array(
                    'DETAILS_READYFORSHIPPING',
                    $lang == 'de'
                        ? 'Sofort lieferbar'
                        : 'Ready for shipping',
                )
            );
        }

        return $stockText;
    }

    /**
     * Get available on if the date value is set on product page
     *
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    private function getAvailableOnMessage(oxArticle $oxArticle)
    {
        $lang                         = $this->marmShopgate->getOxLang()->getLanguageAbbr();
        $result                       = '';
        $isProductAvailableAgainLater = !empty($oxArticle->oxarticles__oxdelivery->value)
            && $oxArticle->oxarticles__oxdelivery->value > date('Y-m-d');

        if ($isProductAvailableAgainLater) {
            $availableOn = $this->getTranslation(
                'ARTICLE_STOCK_DELIVERY',
                array(
                    $lang == 'de'
                        ? 'Wieder lieferbar am'
                        : 'Available on',
                )
            );
            $result      .= ' ' . $availableOn . ' ' . date(
                    'd.m.Y',
                    (strtotime($oxArticle->oxarticles__oxdelivery->value))
                );
        }

        return $result;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getArticleDeliveryTime(oxArticle $oxArticle)
    {
        $minTime        = 0;
        $maxTime        = 0;
        $translateIdent = '';
        $canOrder       = true;

        if (isset($oxArticle->oxarticles__oxmindeltime)) {
            $minTime = $oxArticle->oxarticles__oxmindeltime->value;
        }
        if (isset($oxArticle->oxarticles__oxmaxdeltime)) {
            $maxTime = $oxArticle->oxarticles__oxmaxdeltime->value;
        }
        if (isset($oxArticle->oxarticles__oxdeltimeunit)) {
            $translateIdent = $oxArticle->oxarticles__oxdeltimeunit->value;
        }
        if (isset($oxArticle->oxarticles__oxstockflag->value)) {
            $canOrder = $oxArticle->oxarticles__oxstockflag->value != self::DELIVERY_STATUS_NOT_ORDERABLE;
        }

        $text = '';
        if (!empty($translateIdent) && $canOrder) {
            if ($maxTime > 1 || $minTime > 1) {
                $translateIdent .= 'S';
            }

            if ($maxTime) {
                $text = $this->getTranslation(
                        'PAGE_DETAILS_DELIVERYTIME_DELIVERYTIME',
                        array(
                            'DELIVERY_TIME',
                            'DELIVERYTIME',
                        )
                    ) . ' ';
                if ($minTime) {
                    $text .= $minTime . ' - ';
                }
                $_translateIdent = $this->getTranslation(
                    $translateIdent,
                    array(
                        'PAGE_DETAILS_DELIVERYTIME_' . $translateIdent,
                        'DETAILS_' . $translateIdent,
                    )
                );
                if ($translateIdent != $_translateIdent) {
                    $text .= $maxTime . ' ' . $_translateIdent;
                } else {
                    $text = '';
                }
            }
        }

        return $text;
    }

    /**
     * @param oxArticle $oxArticle
     * @param bool      $isChild
     *
     * @return string
     */
    public function getItemNumber(oxArticle $oxArticle, $isChild = false)
    {
        $uniqueArticleIdField = $this->config->getArticleIdentifier();

        $result = $oxArticle->{"oxarticles__{$uniqueArticleIdField}"}->value;
        if (!$result) {
            $result = $oxArticle->oxarticles__oxid->value;
        }
        if (
            !$oxArticle->getParentArticle()
            && !$oxArticle->sg_act_as_child && $this->config->isVariantParentBuyable()
        ) {
            $result = 'parent' . $result;
        }

        $suffix = $this->getItemNumberSuffix($oxArticle, $isChild, $this->config);
        if (!empty($suffix)) {
            $result .= Shopgate_Model_Export_Product::ARTNUM_SEPARATOR . $suffix;
        }

        return $result;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getItemNumberPublic(oxArticle $oxArticle)
    {
        return $oxArticle->oxarticles__oxartnum->value;
    }

    /**
     * @param oxArticle          $oxArticle
     * @param bool               $isChild
     * @param ShopgateConfigOxid $config
     *
     * @return string
     */
    private function getItemNumberSuffix(oxArticle $oxArticle, $isChild, ShopgateConfigOxid $config)
    {
        $identifier       = $config->getArticleIdentifier();
        $itemNumberSuffix = array();
        $oParent          = $oxArticle->getParentArticle();
        if ($oxArticle->oxarticles__oxparentid->value
            && ($oParent->{"oxarticles__$identifier"}->value == $oxArticle->{"oxarticles__$identifier"}->value)
        ) {
            $attributes = $this->getAttributes($oxArticle, $isChild);
            foreach ($attributes as $attribute) {
                $itemNumberSuffix[] = trim($attribute);
            }
        }

        return implode(Shopgate_Model_Export_Product::ARTNUM_SEPARATOR, $itemNumberSuffix);
    }

    /**
     * @param oxArticle $oxArticle
     * @param bool      $isChild
     *
     * @return array
     */
    public function getAttributes(oxArticle $oxArticle, $isChild)
    {
        $sVariantOptions = $oxArticle->oxarticles__oxvarname->value;

        $oParent = $oxArticle->getParentArticle();

        if (!$oParent && !$isChild) {
            if ($oxArticle->oxarticles__oxvarname->value) {
                $sVariantOptions = $oxArticle->oxarticles__oxvarname->value;
            } elseif (((int) $oxArticle->oxarticles__oxvarcount->value) > 0 && empty($sVariantOptions)) {
                $sVariantOptions = 'Bitte wählen...';
            }
        } else {
            if ($oxArticle->oxarticles__oxvarselect->value) {
                $sVariantOptions = $oxArticle->oxarticles__oxvarselect->value;
            }
            if (empty($sVariantOptions)) {
                $sVariantOptions = '--';
            }
        }

        return explode(Shopgate_Model_Export_Product::OXID_VARIANT_SEPARATOR, $sVariantOptions);
    }

    /**
     * @param $oxArticle
     *
     * @return array
     */
    public function getOxAttributes($oxArticle)
    {
        $result = array();
        if ($oxArticle instanceof oxArticle) {
            foreach ($oxArticle->getAttributes() as $oAttribute) {
                $result[$oAttribute->oxattribute__oxtitle->value] = $oAttribute->oxattribute__oxvalue->value;
            }
            $lang = $this->marmShopgate->getOxLang()->getLanguageAbbr();

            $length = $oxArticle->oxarticles__oxlength->value;
            $width  = $oxArticle->oxarticles__oxwidth->value;
            $height = $oxArticle->oxarticles__oxheight->value;
            if (!empty($length) || !empty($width) || !empty($height)) {
                $dimensionsLabel          = $lang == 'de'
                    ? 'Ma&szlig;e'
                    : 'Dimensions';
                $result[$dimensionsLabel] = "$length * $width * $height m";
            }
        }

        return $result;
    }

    /**
     * Rounds and formats a price.
     *
     * @param float  $price          The price of an item.
     * @param int    $digits         The number of digits after the decimal separator.
     * @param string $decimalPoint   The decimal separator.
     * @param string $thousandPoints The thousands separator.
     *
     * @return float|string
     */
    public function formatPriceNumber($price, $digits = 2, $decimalPoint = ".", $thousandPoints = "")
    {
        $price = round($price, $digits);
        $price = number_format($price, $digits, $decimalPoint, $thousandPoints);

        return $price;
    }

    /**
     * translates given ident, or picks first translation from alternatives
     *
     * @param string $sIdent
     * @param array  $aAlternatives
     *
     * @return string
     */
    public function getTranslation($sIdent, $aAlternatives = array())
    {
        $oLang        = $this->marmShopgate->getOxLang();
        $sTranslation = $oLang->translateString($sIdent);
        if (count($aAlternatives) && $sTranslation == $sIdent) {
            foreach ($aAlternatives as $sAlternative) {
                $sTranslation = $oLang->translateString($sAlternative);
                if ($sAlternative != $sTranslation) {
                    return $sTranslation;
                }
            }
        }

        return $sTranslation;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function parseUrl($url)
    {
        if ($this->config->getHtaccessUser() && $this->config->getHtaccessPassword()) {
            $replacement = "http://";
            $replacement .= urlencode($this->config->getHtaccessUser());
            $replacement .= ":";
            $replacement .= urlencode($this->config->getHtaccessPassword());
            $replacement .= "@";

            $url = preg_replace("/^http:\/\//i", $replacement, $url, 1);
        }

        return $url;
    }

    /**
     * @param string $fileName
     *
     * @return bool
     */
    public function isNopicImage($fileName)
    {
        return preg_match("/nopic.*\./", $fileName);
    }


    ###############################################################################################
    ## Object builders
    ###############################################################################################

    /**
     * @param string $type
     * @param string $value
     *
     * @return Shopgate_Model_Catalog_Identifier
     */
    public function buildIdentifier($type, $value)
    {
        $identifier = new Shopgate_Model_Catalog_Identifier();
        $identifier->setType($type);
        $identifier->setValue($value);

        return $identifier;
    }

    /**
     * @param string $url
     * @param        string null $title
     * @param        string null $alt
     *
     * @return Shopgate_Model_Media_Image
     */
    public function buildImageObject($url, $title = null, $alt = null)
    {
        $image = new Shopgate_Model_Media_Image();
        $image->setUrl($this->parseUrl($url));
        if (!empty($title)) {
            $image->setTitle($title);
        }
        if (!empty($alt)) {
            $image->setAlt($alt);
        }

        return $image;
    }

    /**
     * @param string $label
     * @param string $value
     *
     * @return Shopgate_Model_Catalog_Property
     */
    public function buildProperty($label, $value)
    {
        $property = new Shopgate_Model_Catalog_Property();
        $property->setLabel($label);
        $property->setValue($value);

        return $property;
    }

    /**
     * @param string $type
     * @param array  $values
     *
     * @return Shopgate_Model_Catalog_Relation
     */
    public function buildRelation($type, array $values)
    {
        $relation = new Shopgate_Model_Catalog_Relation();
        $relation->setType($type);
        $relation->setValues($values);

        return $relation;
    }


    ###############################################################################################
    ## Csv
    ###############################################################################################

    /**
     * @param Shopgate_Model_Catalog_Category[] $categories
     *
     * @return string
     */
    public function getCategoryNumbers(array $categories = array())
    {
        $numbers = array();
        foreach ($categories as $category) {
            $numbers[] = $category->getUid() . '=>' . $category->getSortOrder();
        }

        return implode(Shopgate_Model_Export_Product::MULTI_SEPARATOR, $numbers);
    }

    /**
     * @param oxArticle $oxArticle
     * @param bool      $isChild
     *
     * @return string
     */
    public function getParentItemNumber(oxArticle $oxArticle, $isChild)
    {
        if (($oParentArticle = $oxArticle->getParentArticle()) || $isChild) {
            if (!$oParentArticle) {
                $oParentArticle = $oxArticle;
            }
            $oParentArticle->sg_act_as_child = false;

            return $this->getItemNumber($oParentArticle, $isChild);
        }

        return '';
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return int
     */
    public function getUseStock(oxArticle $oxArticle)
    {
        if ($this->config->getSysUseStock()
            && $oxArticle->oxarticles__oxstockflag->value != self::DELIVERY_STATUS_STOREHOUSE
        ) {
            return 1;
        }

        return 0;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return Shopgate_Model_Media_Image[]
     */
    public function getImages(oxArticle $oxArticle)
    {
        $images      = array();
        $aPicGallery = $oxArticle->getPictureGallery();

        if (!empty($aPicGallery['ZoomPics'])) {
            foreach ($aPicGallery['ZoomPics'] as $aPic) {
                if (!$this->isNopicImage($aPic['file'])) {
                    $images[] = $this->buildImageObject($aPic['file']);
                }
            }
        } elseif (!empty($aPicGallery['Pics'])) {
            foreach ($aPicGallery['Pics'] as $aPic) {
                if (!$this->isNopicImage($aPic)) {
                    $images[] = $this->buildImageObject($aPic);
                }
            }
        } elseif (!empty($aPicGallery['ActPic'])) {
            if (!$this->isNopicImage($aPicGallery['ActPic'])) {
                $images[] = $this->buildImageObject($aPicGallery['ActPic']);
            }
        } else {
            if (!$this->isNopicImage($oxArticle->getThumbnailUrl())) {
                $images[] = $this->buildImageObject($oxArticle->getThumbnailUrl());
            }
        }

        return $images;
    }

    /**
     * @param Shopgate_Model_Media_Image[] $images
     *
     * @return string
     */
    public function getImageUrls(array $images = array())
    {
        $urls = array();
        foreach ($images as $image) {
            $urls[] = $image->getUrl();
        }

        return implode(Shopgate_Model_Export_Product::MULTI_SEPARATOR, $urls);
    }

    /**
     * Return the Options for the Article
     *
     * Format:
     * <code>
     * [0] => Array(
     *   [label] => Farbe,
     *   [values] => Array(
     *     [0] => 0=grün=>111
     *     [1] => 1=blau=>222
     *   )
     * )
     * </code>
     *
     * @param oxArticle $oxArticle
     * @param bool      $isChild
     *
     * @return array
     */
    public function getOptions(oxArticle $oxArticle, $isChild)
    {
        if ($oxArticle->oxarticles__oxvarcount->value && !$isChild) {
            // parent products don't need options - their children have options
            return array();
        }

        if (!method_exists($oxArticle, 'getSelections')) {
            return $this->getOptionsForArticle_beforeOxid450($oxArticle);
        }

        $aOptions = array();

        /** @var oxselectlist[] $oSelectionList */
        $oSelectionList = $oxArticle->getSelections();
        if (empty($oSelectionList)) {
            return $aOptions;
        }

        foreach ($oSelectionList as $oListItem) {
            $aSelections = $this->marmShopgate->getOxUtils()->assignValuesFromText(
                $oListItem->oxselectlist__oxvaldesc->getRawValue(),
                $oListItem->getVat()
            );
            if ($aSelections) {
                $_aSelections           = array();
                $_aSelections['id']     = $oListItem->getId();
                $_aSelections['label']  = $oListItem->getLabel();
                $_aSelections['values'] = array();

                $i = 0;
                foreach ($aSelections as $oSelection) {
                    $price                    = $this->formatPriceNumber($oxArticle->getPrice()->getBruttoPrice());
                    $_aSelections['values'][] = $this->getOptionValue($oSelection, $price, $i);
                    $i++;
                }
                $aOptions[] = $_aSelections;
            }
        }

        return $aOptions;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return array
     */
    private function getOptionsForArticle_beforeOxid450(oxArticle $oxArticle)
    {
        $aOptions = array();

        $aSelectionLists = $oxArticle->getSelectLists();
        if (!$aSelectionLists) {
            return $aOptions;
        }

        foreach ($aSelectionLists as $aSelectionList) {
            $_aSelections           = array();
            $_aSelections['label']  = $aSelectionList['name'];
            $_aSelections['values'] = array();

            $i = 0;
            foreach ($aSelectionList as $oListItem) {
                if (is_object($oListItem) && isset($oListItem->name)) {
                    $price                    = $this->formatPriceNumber($oxArticle->getPrice()->getBruttoPrice());
                    $_aSelections['values'][] = $this->getOptionValue($oListItem, $price, $i);
                    $i++;
                }
            }
            $aOptions[] = $_aSelections;
        }

        return $aOptions;
    }

    /**
     * @param        $oSelection
     * @param        $item_price
     * @param string $iCount
     *
     * @return array
     */
    private function getOptionValue($oSelection, $item_price, $iCount = '0')
    {
        if (empty($iCount)) {
            $iCount = '0';
        }
        $price = 0;
        if (isset($oSelection->price)) {
            if ($oSelection->priceUnit === 'abs') {
                $price = $oSelection->price * 100;
            } elseif ($oSelection->priceUnit === '%') {
                $price = $item_price * (1 + ($oSelection->price / 100));

                $price = $price - $item_price;
                $price = $price * 100;
            }
        }
        $price = round($price, 0);

        // The currency-sign
        $sign = $this->marmShopgate->getOxUtils()->getConfig()->getActShopCurrencyObject()->sign;
        // remove priceinformation (it will be shown at shopgate)
        $name = preg_replace("/\ [\+\-]\d+[\,\.]\d+\ {$sign}$/", "", $oSelection->name);

        return array(
            'id'    => $iCount,
            'name'  => $name,
            'price' => $price,
        );
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return array
     */
    public function getChildArticleIds(oxArticle $oxArticle)
    {
        $articleTable     = $oxArticle->getViewName();
        $sqlActiveSnippet = $oxArticle->getSqlActiveSnippet();
        $sqlActiveSnippet .= " AND marm_shopgate_export = 1 ";
        $sqlActiveSnippet .= " AND ( OXPARENTID = '{$oxArticle->getId()}' )";
        $select           = "SELECT OXID FROM $articleTable WHERE $sqlActiveSnippet ORDER BY OXID ASC";
        $ids              = $this->marmShopgate->dbGetAll($select);
        $result           = array();
        foreach ($ids as $id) {
            $result[] = array_shift($id);
        }

        return $result;
    }

    /**
     * formats oxarticle object for list usage. will be used to clone and load data from DB
     *
     * @return oxArticle
     */
    public function getArticleBase()
    {
        /** @var oxArticle $oArticleBase */
        $oArticleBase = oxNew('oxArticle', array('_blUseLazyLoading' => false));

        if (method_exists($oArticleBase, 'setSkipAbPrice')) {
            $oArticleBase->setSkipAbPrice(true);
        }
        $oArticleBase->setLoadParentData(false);
        $oArticleBase->setNoVariantLoading(true);

        return $oArticleBase;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getActiveStatus(oxArticle $oxArticle)
    {
        $result = ShopgatePlugin::PRODUCT_STATUS_STOCK;

        // Active but not orderable if out of stock
        if ($oxArticle->oxarticles__oxstockflag->value == self::DELIVERY_STATUS_NOT_ORDERABLE) {
            $result = ShopgatePlugin::PRODUCT_STATUS_ACTIVE;
        }

        return $result;
    }

    /**
     * @example 4.27 €/liter
     *
     * @param oxArticle $oxArticle
     *
     * @return float|string
     */
    public function getBasicPrice(oxArticle $oxArticle)
    {
        $currency   = $this->marmShopgate->getOxConfig()->getActShopCurrencyObject();
        $basicPrice = '';
        if (!empty($oxArticle->oxarticles__oxunitquantity->value) && !empty($oxArticle->oxarticles__oxunitname->value)) {
            $price    = $oxArticle->getPrice()->getBruttoPrice();
            $quantity = $oxArticle->oxarticles__oxunitquantity->value;
            $unit     = $this->marmShopgate->getOxLang()->translateString($oxArticle->oxarticles__oxunitname->value);
            if (isset($this->unitMultiplicators[$unit])) {
                $price *= $this->unitMultiplicators[$unit];
                $unit  = $this->unitMultiplicators[$unit] . "$unit";
            }

            $basicPrice = $this->formatPriceNumber($price / $quantity) . " $currency->name / $unit";
        }

        return $basicPrice;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return float
     */
    public function getCost(oxArticle $oxArticle)
    {
        return oxPrice::brutto2Netto(
                $oxArticle->oxarticles__oxbprice->value,
                $oxArticle->getArticleVat()
            ) * $this->getVpe($oxArticle);
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        $currency = $this->marmShopgate->getOxConfig()->getActShopCurrencyObject();

        return $currency->name;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getDeeplink(oxArticle $oxArticle)
    {
        if ($this->marmShopgate->getOxUtils()->seoIsActive()) {
            // only if native SEO URLs used. We want to remove some session GET variables at the end of the link
            $url = $this->marmShopgate->cleanUrl($oxArticle->getLink());
        } else {
            $url = html_entity_decode($oxArticle->getLink());
        }

        return $this->parseUrl($url);
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getDescription(oxArticle $oxArticle)
    {
        try {
            $this->marmShopgate->getOxUtilsView()->getSmarty()->assign('oDetailsProduct', $oxArticle);

            if (method_exists($oxArticle, 'getArticleLongDesc')) {
                $oLongDesc = $oxArticle->getArticleLongDesc();

                if (null !== $oLongDesc->rawValue) {
                    $description = $oLongDesc->rawValue;
                } else {
                    $description = $oLongDesc->value;
                }

                $description = $this->marmShopgate->getOxUtilsView()->parseThroughSmarty(
                    $description,
                    $oxArticle->getId() . $oxArticle->getLanguage(),
                    null,
                    true
                );
            } else {
                $description = $oxArticle->getLongDesc();
            }
        } catch (Exception $e) {
            $description = $oxArticle->getLongDesc();
            ob_clean();
        }
        $description = mb_ereg_replace('/<!--\[if gte mso [0-9]+\]>(.|\n)*<!\[endif\]-->/U', '', $description);

        return $description;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getEAN(oxArticle $oxArticle)
    {
        return $oxArticle->oxarticles__oxean->value;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return Shopgate_Model_Catalog_Input[]
     */
    public function getInputs(oxArticle $oxArticle)
    {
        $inputs = array();
        if ($oxArticle->oxarticles__oxisconfigurable->value) {
            $input = new Shopgate_Model_Catalog_Input();
            $input->setUid(1);
            $input->setRequired(false);
            $input->setType(Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_TEXT);
            $input->setLabel($this->getTranslation('LABEL', array('PAGE_DETAILS_PERSPARAM_LABEL', 'DETAILS_LABEL')));
            $inputs[] = $input;
        }

        return $inputs;
    }

    public function getInternalOrderInfo(oxArticle $oxArticle)
    {
        $infos                 = array();
        $infos['article_oxid'] = $oxArticle->getId();

        $vpe = $this->getVpe($oxArticle);
        if ($vpe > 1) {
            $infos['vpe'] = $vpe;
        }

        return $infos;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return bool
     */
    public function getIsFreeShipping(oxArticle $oxArticle)
    {
        return $oxArticle->oxarticles__oxfreeshipping->value;
    }

    public function getIsHighlight(oxArticle $oxArticle)
    {
        return in_array($oxArticle->getId(), $this->highlightItemsCache);
    }

    public function getIsMarketplace(oxArticle $oxArticle)
    {
        return $oxArticle->oxarticles__marm_shopgate_marketplace->value;
    }

    public function getIsSaleable(oxArticle $oxArticle)
    {
        if (isset($oxArticle->oxarticles__tc_isbuyable->value)) {
            return $oxArticle->oxarticles__tc_isbuyable->value && $oxArticle->isBuyable();
        }

        return $oxArticle->isBuyable();
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getLastUpdate(oxArticle $oxArticle)
    {
        $timestamp = strtotime($oxArticle->oxarticles__oxtimestamp->value);
        if (!empty($timestamp)) {
            return date('Y-m-d', $timestamp);
        }

        return '';
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return float
     */
    public function getManufacturerItemNumber(oxArticle $oxArticle)
    {
        return $oxArticle->oxarticles__oxmpn->value;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return float
     */
    public function getgetManufacturerTitle(oxArticle $oxArticle)
    {
        $uid = $oxArticle->oxarticles__oxmanufacturerid->value;

        return isset($this->manufacturersCache[$uid])
            ? $this->manufacturersCache[$uid]
            : '';
    }

    /**
     * article recommended retail price (RRP)
     *
     * @example 99.95
     *
     * @param oxArticle $oxArticle
     * @param bool      $asNet
     *
     * @return float|string
     */
    public function getMsrp(oxArticle $oxArticle, $asNet)
    {
        if ($oxArticle->getTPrice()) { // Can be null
            if ($asNet) {
                // Don't use $oxArticle->getTPrice()->getNettoPrice() here, 'cause it gives us only a rounded value.
                return oxPrice::brutto2Netto(
                        $oxArticle->getTPrice()->getBruttoPrice(),
                        $oxArticle->getArticleVat()
                    ) * $this->getVpe($oxArticle);
            }

            return $oxArticle->getTPrice()->getBruttoPrice() * $this->getVpe($oxArticle);
        }

        return '';
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getName(oxArticle $oxArticle)
    {
        $type = $this->config->getArticleNameExportType();
        if ($type == ShopgateConfigOxid::ARTICLE_NAME_EXPORT_TYPE_BOTH && !empty($oxArticle->oxarticles__oxshortdesc->value)) {
            $name = $oxArticle->oxarticles__oxtitle->value . ' ' . $oxArticle->oxarticles__oxshortdesc->value;
        } elseif ($type == ShopgateConfigOxid::ARTICLE_NAME_EXPORT_TYPE_SHORTDESC && !empty($oxArticle->oxarticles__oxshortdesc->value)) {
            $name = $oxArticle->oxarticles__oxshortdesc->value;
        } else {
            $name = $oxArticle->oxarticles__oxtitle->value;
        }

        $vpe = $this->getVpe($oxArticle);
        if ($vpe > 1) {
            $name .= " ({$vpe}er Packung)";
        }

        return $name;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return Shopgate_Model_Catalog_Property[]
     */
    public function getProperties(oxArticle $oxArticle)
    {
        $properties = array();

        $properties[] = $this->buildProperty('Art.Nr.', $oxArticle->oxarticles__oxartnum->value);

        if (!empty($oxArticle->oxarticles__oxshortdesc->value)) {
            $shortDesc = $oxArticle->oxarticles__oxshortdesc->value;
            $shortDesc = html_entity_decode($shortDesc);
            $shortDesc = strip_tags($shortDesc);

            $lang           = $this->marmShopgate->getOxLang()->getLanguageAbbr();
            $shortDescLabel = $this->getTranslation(
                'OXSHORTDESC',
                array(
                    $lang == 'de'
                        ? 'Kurzbeschreibung'
                        : 'Short description',
                )
            );

            $properties[] = $this->buildProperty($shortDescLabel, $shortDesc);
        }

        if (!empty($oxArticle->oxarticles__oxexturl->value)) {
            $linkLabel = $oxArticle->oxarticles__oxurldesc->value;
            if (empty($linkLabel)) {
                $linkLabel = 'Link';
            }
            $linkUrl = $oxArticle->oxarticles__oxexturl->value;
            if (stripos($linkUrl, 'http:') === false) {
                $linkUrl = "http://$linkUrl";
            }
            $properties[] = $this->buildProperty($linkLabel, "<a href='$linkUrl'>$linkUrl</a>");
        }

        // We also add the parent product's attributes (if not already present). (Oxid does this itself but it didn't work in Versions < 4.7.1)
        $attributes =
            $this->getOxAttributes($oxArticle) + $this->getOxAttributes($oxArticle->getParentArticle());
        foreach ($attributes as $name => $value) {
            $properties[] = $this->buildProperty($name, $value);
        }

        $vpe = $this->getVpe($oxArticle);
        if ($vpe > 1) {
            $properties[] = $this->buildProperty('Menge', "{$vpe}er Packung");
        }

        return $properties;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getPropertiesCsv(oxArticle $oxArticle)
    {
        $result = array();
        foreach ($this->getProperties($oxArticle) as $property) {
            $result[] = $property->getLabel() . '=>' . $property->getValue();
        }

        return implode('||', $result);
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return int
     */
    public function getStockQuantity(oxArticle $oxArticle)
    {
        return floor($oxArticle->oxarticles__oxstock->value / $this->getVpe($oxArticle));
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return Shopgate_Model_Catalog_Tag[]
     */
    public function getTags(oxArticle $oxArticle)
    {
        if (method_exists($oxArticle, 'getTags')) {
            return $this->getTagsByArticleTags($oxArticle);
        } elseif (class_exists('oxArticleTagList')) {
            return $this->getTagsByArticleTagList($oxArticle);
        }

        return array();
    }

    /**
     * method used for older versions
     *
     * @param oxArticle $oxArticle
     *
     * @return array
     */
    private function getTagsByArticleTags($oxArticle)
    {
        $tags = array();
        /** @noinspection PhpDeprecationInspection */
        foreach (explode(',', $oxArticle->getTags()) as $oxTag) {
            $tag = new Shopgate_Model_Catalog_Tag();
            $tag->setValue($oxTag);
            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * method used for newer versions - tested in 4.9.5 and 4.9.8
     *
     * @param oxArticle $oxArticle
     *
     * @return array
     */
    private function getTagsByArticleTagList($oxArticle)
    {
        $tags            = array();
        $oArticleTagList = oxNew("oxArticleTagList");
        $oArticleTagList->loadInLang(marm_shopgate::getOxLang()->getEditLanguage(), $oxArticle->getId());
        $oTagSet = $oArticleTagList->get();

        if (!$oTagSet instanceof oxTagSet) {
            return $tags;
        }

        foreach ($oTagSet as $oxTag) {
            /** @var oxTag $oxTag */
            $tag = new Shopgate_Model_Catalog_Tag();
            $tag->setValue($oxTag->getTitle());
            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getTagsCsv(oxArticle $oxArticle)
    {
        $result = array();
        foreach ($this->getTags($oxArticle) as $tag) {
            $result[] = $tag->getValue();
        }

        return implode(',', $result);
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getTaxClass(oxArticle $oxArticle)
    {
        return "tax_{$oxArticle->getArticleVat()}";
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return float
     */
    public function getTaxPercent(oxArticle $oxArticle)
    {
        return $oxArticle->getArticleVat();
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return float
     */
    public function getWeight(oxArticle $oxArticle)
    {
        return $oxArticle->oxarticles__oxweight->value;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return string
     */
    public function getWeightUnit(oxArticle $oxArticle)
    {
        return Shopgate_Model_Catalog_Product::DEFAULT_WEIGHT_UNIT_KG;
    }

    /**
     * @param oxArticle $oxArticle
     *
     * @return Shopgate_Model_Catalog_TierPrice[]
     */
    public function getTierPrices(oxArticle $oxArticle)
    {
        $result          = array();
        $amountPriceInfo = $oxArticle->loadAmountPriceInfo();
        if ($amountPriceInfo instanceof oxAmountPriceList) {
            $amountPriceInfo = $amountPriceInfo->getArray();
        }
        $price         = $this->getUnitAmountNet($oxArticle);
        $purchaseSteps = $this->getVpe($oxArticle);
        foreach ($amountPriceInfo as $priceItem) {
            $tierPrice = $this->getPriceForQuantityAndGroup($oxArticle, $priceItem->oxprice2article__oxamount->value);

            $tierPriceObject = new Shopgate_Model_Catalog_TierPrice();
            $tierPriceObject->setReductionType(Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_FIXED);
            $tierPriceObject->setReduction(($price - $tierPrice) * $purchaseSteps);
            $fromQuantity = $priceItem->oxprice2article__oxamount->value;
            $toQuantity   = $priceItem->oxprice2article__oxamountto->value;
            $tierPriceObject->setFromQuantity(floor(($fromQuantity - 1 / $purchaseSteps) + 1));
            $tierPriceObject->setToQuantity(floor($toQuantity / $purchaseSteps));
            $result[] = $tierPriceObject;
        }

        foreach (array('a', 'b', 'c') as $group) {
            if ($groupPrice = $this->buildCustomerGroupTierPrice(clone $oxArticle, "oxidprice{$group}")) {
                $result[] = $groupPrice;
            }
        }

        return $result;
    }

    /**
     * @param oxArticle $oxArticle
     * @param string    $groupId
     *
     * @return Shopgate_Model_Catalog_TierPrice
     */
    public function buildCustomerGroupTierPrice(oxArticle $oxArticle, $groupId)
    {
        $regularPrice = $this->getUnitAmountNet($oxArticle);
        $groupPrice   = $this->getPriceForQuantityAndGroup($oxArticle, 1, $groupId);
        if ($groupPrice < 0.0001 || abs($regularPrice - $groupPrice) < 0.0001) {
            // oxPrice::brutto2Netto converts for rounding values into double which implies imprecise values.
            return null;
        }

        $result = new Shopgate_Model_Catalog_TierPrice();
        $result->setReductionType(Shopgate_Model_Catalog_TierPrice::DEFAULT_TIER_PRICE_TYPE_FIXED);
        $result->setReduction($regularPrice - $groupPrice);
        $result->setCustomerGroupUid($groupId);
        $result->setFromQuantity(1);

        return $result;
    }

    /**
     * @param oxArticle $oxArticle
     * @param int       $quantity
     * @param string    $groupId
     *
     * @return float
     */
    public function getPriceForQuantityAndGroup(oxArticle $oxArticle, $quantity, $groupId = null)
    {
        /** @var oxUser $oxUser */
        $oxUser = oxNew('oxUser');
        $oxUser->removeFromGroup('oxidpricea');
        $oxUser->removeFromGroup('oxidpriceb');
        $oxUser->removeFromGroup('oxidpricec');
        if (!empty($groupId)) {
            $oxUser->addToGroup($groupId);
        }

        /** @var oxBasket $oxBasket */
        $oxBasket = oxNew('oxBasket');
        $oxBasket->setBasketUser($oxUser);

        $basketPrice = $oxArticle->getBasketPrice($quantity, null, $oxBasket);

        return oxPrice::brutto2Netto($basketPrice->getBruttoPrice(), $oxArticle->getArticleVat());
    }
}
