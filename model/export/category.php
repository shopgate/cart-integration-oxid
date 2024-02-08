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

class Shopgate_Model_Export_Category extends Shopgate_Model_Catalog_Category
{
    const ROOT_CATEGORY_ID = 'oxrootid';

    /** @var oxCategory */
    protected $item;

    /** @var ShopgateConfigOxid */
    private $config;

    /** @var int */
    private $maxSort;

    ###############################################################################################
    ## Setters for local variables
    ###############################################################################################

    /**
     * @param ShopgateConfigOxid $config
     */
    public function setConfig(ShopgateConfigOxid $config)
    {
        $this->config = $config;
    }

    /**
     * @param int $maxSort
     */
    public function setMaxSort($maxSort)
    {
        $this->maxSort = $maxSort;
    }

    ###############################################################################################
    ## Setters for category data
    ###############################################################################################

    public function setDeeplink()
    {
        $url = $this->parseUrl(marm_shopgate::cleanUrl($this->item->getLink()));
        parent::setDeeplink($url);
    }

    public function setImage()
    {
        $imageUrl = "";
        if (!empty($this->item->oxcategories__oxicon->value)) {
            $imageUrl = $this->item->getPictureUrl() . $this->item->oxcategories__oxicon->value;
        } elseif (!empty($this->item->oxcategories__oxthumb->value)) {
            $imageUrl = $this->item->getPictureUrl() . $this->item->oxcategories__oxthumb->value;
        }

        $image = new Shopgate_Model_Media_Image();
        $image->setUrl($imageUrl);
        parent::setImage($image);
    }

    public function setIsActive()
    {
        $isActive = $this->item->oxcategories__oxactive->value == 1 && $this->item->oxcategories__oxhidden->value == 0;
        parent::setIsActive($isActive);
    }

    public function setIsAnchor()
    {
        parent::setIsAnchor(false);
    }

    public function setName()
    {
        parent::setName($this->item->oxcategories__oxtitle->value);
    }

    public function setParentUid()
    {
        $parentId = $this->item->oxcategories__oxparentid->value;
        if ($parentId == self::ROOT_CATEGORY_ID) {
            $parentId = "";
        }
        parent::setParentUid($parentId);
    }

    public function setSortOrder()
    {
        parent::setSortOrder($this->maxSort - $this->item->oxcategories__oxsort->value);
    }

    public function setUid()
    {
        parent::setUid($this->item->oxcategories__oxid->value);
    }

    ###############################################################################################
    ## Helpers
    ###############################################################################################

    /**
     * @param string $url
     *
     * @return string
     */
    private function parseUrl($url)
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

    ###############################################################################################
    ## Csv
    ###############################################################################################

    /**
     * @return array
     */
    public function asCsv()
    {
        return array(
            'category_number' => $this->getUid(),
            'category_name'   => $this->getName(),
            'parent_id'       => $this->getParentUid(),
            'url_image'       => $this->getImage()->getUrl(),
            'order_index'     => $this->getSortOrder(),
            'is_active'       => $this->getIsActive()
                ? 1
                : 0,
            'url_deeplink'    => $this->getDeeplink(),
        );
    }
}
