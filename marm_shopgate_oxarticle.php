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

class marm_shopgate_oxarticle extends marm_shopgate_oxarticle_parent
{
    /**
     * @var ShopgateOrderItem
     */
    public $sg_order_item;

    public function save()
    {
        if (!$this->exists()) {
            $this->oxarticles__marm_shopgate_marketplace = new oxField("1", oxField::T_RAW);
            $this->oxarticles__marm_shopgate_export      = new oxField("1", oxField::T_RAW);
        }
        $blResult = parent::save();

        return $blResult;
    }

    public function isBuyable()
    {
        // disable buyable check on place order from shopgate
        if (
            defined("_SHOPGATE_API") && _SHOPGATE_API
            && defined('_SHOPGATE_ACTION') && in_array(_SHOPGATE_ACTION, array('add_order', 'update_order'))
        ) {
            return true;
        }

        return parent::isBuyable();
    }

    public function checkForStock($dAmount, $dArtStockAmount = 0, $selectForUpdate = false)
    {
        // disable stock check on place order from shopgate
        if (
            defined("_SHOPGATE_API") && _SHOPGATE_API
            && defined("_SHOPGATE_ACTION")
            && in_array(_SHOPGATE_ACTION, array("add_order", "update_order"))
        ) {
            return true;
        }

        return parent::checkForStock($dAmount, $dArtStockAmount, $selectForUpdate);
    }

    public function isVisible()
    {
        // disable stock check on place order from shopgate
        if (
            defined("_SHOPGATE_API") && _SHOPGATE_API
            && defined("_SHOPGATE_ACTION")
            && in_array(_SHOPGATE_ACTION, array("add_order", "update_order"))
        ) {
            return true;
        }

        return parent::isVisible();
    }

    /**
     * @param float             $dAmount
     * @param string            $aSelList
     * @param shopgate_oxbasket $oBasket
     *
     * @return oxPrice
     */
    public function getBasketPrice($dAmount, $aSelList, $oBasket)
    {
        if (defined("_SHOPGATE_API") && _SHOPGATE_API && $this->sg_order_item) {
            $tax   = $this->sg_order_item->getTaxPercent();
            $price = $oBasket->isCalculationModeNetto()
                ? $this->sg_order_item->getUnitAmount()
                : $this->sg_order_item->getUnitAmountWithTax();
            $infos = $this->jsonDecode($this->sg_order_item->getInternalOrderInfo(), true);

            if (!empty($infos['vpe']) && is_numeric($infos['vpe']) && $infos['vpe'] > 1) {
                $price = $price / $infos['vpe'];
            }

            /** @var oxPrice $oBasketPrice */
            $oBasketPrice = oxNew('oxPrice');
            if ($oBasket->isCalculationModeNetto()) {
                $oBasketPrice->setNettoPriceMode();
            } else {
                $oBasketPrice->setBruttoPriceMode();
            }
            $oBasketPrice->setVat($tax);
            $oBasketPrice->setPrice($price);

            return $oBasketPrice;
        }

        return parent::getBasketPrice($dAmount, $aSelList, $oBasket);
    }

    /**
     * Workaround for Oxid 4.1
     */
    public function getParentArticle()
    {
        if (method_exists('oxArticle', 'getParentArticle')) {
            return parent::getParentArticle();
        }
        // ATTN despite the typo (Aricle) this IS correct
        if (method_exists('oxArticle', '_getParentAricle')) {
            /** @noinspection PhpUndefinedMethodInspection PhpDeprecationInspection */
            return $this->_getParentAricle();
        }
        throw new ShopgateLibraryException("Can't get parent article.");
    }

    private function jsonDecode($json, $assoc = false)
    {
        // if json_decode exists use that
        if (extension_loaded('json') && function_exists('json_decode')) {
            return json_decode($json, $assoc);
        }

        // if not check if external class is loaded
        if (!class_exists('sgServicesJSON')) {
            require_once dirname(__FILE__) . '/shopgate_library/JSON.php';
        }

        // decode via external class
        $jsonService = new sgServicesJSON(
            ($assoc)
                ? sgServicesJSON_LOOSE_TYPE
                : sgServicesJSON_IN_OBJ
        );

        return $jsonService->decode($json);
    }
}
