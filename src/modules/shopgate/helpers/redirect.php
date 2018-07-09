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

class ShopgateRedirectHelper extends ShopgateObject
{
    /**
     * @param string   $objectId
     * @param int|null $shopId
     * @param int|null $languageId
     *
     * @return string
     */
    public static function getSeoUrl($objectId, $shopId = null, $languageId = null)
    {
        if (is_null($shopId)) {
            $shopId = marm_shopgate::getOxConfig()->getShopId();
        }
        if (is_null($languageId)) {
            $languageId = marm_shopgate::getOxLang()->getBaseLanguage();
        }
        $sQ = "SELECT `oxseourl` FROM `oxseo` WHERE `oxobjectid` = '" . $objectId
            . "' AND `oxlang` = '$languageId' AND `oxshopid` = '$shopId' LIMIT 1";
        $seoUrl = marm_shopgate::getDb()->getOne($sQ);

        return (string)$seoUrl;
    }
}
