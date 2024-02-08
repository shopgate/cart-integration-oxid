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

class Shopgate_Model_Export_Product extends Shopgate_Model_Catalog_Product
{
    const ARTNUM_SEPARATOR       = '-';
    const MULTI_SEPARATOR        = '||';
    const OXID_VARIANT_SEPARATOR = '|';
    const PARENT_SEPARATOR       = '=>';

    /** @var oxArticle */
    protected $item;

    /** @var oxArticle */
    protected $parent = null;

    /** @var bool */
    private $useTaxClasses;

    /** @var ShopgateItemExportHelper */
    private $shopgateItemExportHelper;

    /** @var array */
    protected $fireMethods = array(
        'setAttributeGroups',
        'setCategoryPaths',
        'setChildren',
        'setCurrency',
        'setDeeplink',
        'setDescription',
        'setDisplayType',
        'setIdentifiers',
        'setImages',
        'setInputs',
        'setInternalOrderInfo',
        'setLastUpdate',
        'setManufacturer',
        'setName',
        'setPrice',
        'setProperties',
        'setRelations',
        'setShipping',
        'setStock',
        'setTags',
        'setTaxClass',
        'setTaxPercent',
        'setUid',
        'setVisibility',
        'setWeight',
        'setWeightUnit',
    );

    /**
     * @param ShopgateItemExportHelper $shopgateItemExportHelper
     */
    public function __construct(ShopgateItemExportHelper $shopgateItemExportHelper)
    {
        parent::__construct();
        $this->shopgateItemExportHelper = $shopgateItemExportHelper;
    }


    ###############################################################################################
    ## Setters for local variables
    ###############################################################################################

    /**
     * @param boolean $useTaxClasses
     */
    public function setUseTaxClasses($useTaxClasses)
    {
        $this->useTaxClasses = $useTaxClasses;
    }

    ###############################################################################################
    ## Setters for category data
    ###############################################################################################

    public function setAttributeGroups()
    {
        $oParent = $this->item->getParentArticle();
        if ($oParent || $this->isChild || empty($this->item->oxarticles__oxvarcount->value)) {
            return;
        }

        if ($this->item->oxarticles__oxvarname->value) {
            $oxVarNames = $this->item->oxarticles__oxvarname->value;
        } elseif (((int)$this->item->oxarticles__oxvarcount->value) > 0 && empty($oxVarNames)) {
            $oxVarNames = 'Bitte wählen...';
        }
        $oxVarNames = explode(self::OXID_VARIANT_SEPARATOR, $oxVarNames);

        $i               = 1;
        $attributeGroups = array();
        foreach ($oxVarNames as $oxVarName) {
            $attributeGroup = new Shopgate_Model_Catalog_AttributeGroup();
            $attributeGroup->setLabel($oxVarName);
            $attributeGroup->setUid($i);
            $attributeGroups[] = $attributeGroup;
            $i++;
        }

        parent::setAttributeGroups($attributeGroups);
    }

    public function setAttributes()
    {
        $oParent = $this->item->getParentArticle();
        if (!$oParent && !$this->isChild) {
            return;
        }

        if ($this->item->oxarticles__oxvarselect->value) {
            $oxVarSelects = $this->item->oxarticles__oxvarselect->value;
        }
        if (empty($oxVarSelects)) {
            $oxVarSelects = '--';
        }
        $oxVarSelects = explode(self::OXID_VARIANT_SEPARATOR, $oxVarSelects);

        $i          = 1;
        $attributes = array();
        foreach ($oxVarSelects as $oxVarSelect) {
            $attribute = new Shopgate_Model_Catalog_Attribute();
            $attribute->setLabel($oxVarSelect);
            $attribute->setGroupUid($i);
            $attributes[] = $attribute;
            $i++;
        }

        parent::setAttributes($attributes);
    }

    public function setCategoryPaths()
    {
        if (!empty($this->item->oxarticles__oxsortdate->value)) {
            # see OXID-165
            $index = strtotime($this->item->oxarticles__oxsortdate->value);
        } elseif (!empty($this->item->oxarticles__dd_zeit_neue_artikel->value)) {
            # see OXID-174
            $index = strtotime($this->item->oxarticles__dd_zeit_neue_artikel->value);
        }
        $index = (empty($index) || $index < 0)
            ? 0
            : $index;

        $result = array();
        foreach ($this->getCategoryIds($this->item) as $categoryId => $orderIndex) {
            $category = new Shopgate_Model_Catalog_CategoryPath();
            $category->setUid($categoryId);
            $category->setSortOrder(
                !empty($index)
                    ? $index
                    : $orderIndex
            );
            $result[] = $category;
        }
        parent::setCategoryPaths($result);
    }

    /**
     * @example array(34=>2,46=>3,56=>5)
     *
     * @param oxArticle $oxArticle
     *
     * @return array
     */
    private function getCategoryIds(oxArticle $oxArticle)
    {
        $catIds = $oxArticle->getCategoryIds();
        $prodId = $oxArticle->getId();

        $result = array();
        foreach ($catIds as $catId) {
            if (isset($this->shopgateItemExportHelper->itemInCategorySortCache[$catId][$prodId])) {
                $result[$catId] = $this->shopgateItemExportHelper->itemInCategorySortCache[$catId][$prodId];
            } else {
                /** @var oxcategory $oxCategory */
                $oxCategory = oxNew('oxcategory');
                $oxCategory->load($catId);

                $sortField     = $oxCategory->oxcategories__oxdefsort->value;
                $sortDirection = $oxCategory->oxcategories__oxdefsortmode->value;
                $sortDirection = $sortDirection == "0"
                    ? "desc"
                    : "asc";

                if ($sortField) {
                    $select = "
						SELECT a.oxid
						FROM `oxobject2category` oc
						JOIN `{$oxArticle->getViewName()}` a ON (a.oxid = oc.oxobjectid)
						WHERE oc.oxcatnid = ?
						ORDER BY a.`$sortField` $sortDirection
					";

                    $list = marm_shopgate::dbGetAll($select, array($catId));

                    $orderIndex = 1;
                    foreach ($list as $row) {
                        if (array_shift($row) == $prodId) {
                            $result[$catId] = $orderIndex;
                            break;
                        }
                        $orderIndex++;
                    }
                }
            }
        }

        return $result;
    }

    public function setChildren()
    {
        $children = array();
        $childIds = $this->shopgateItemExportHelper->getChildArticleIds($this->item);
        foreach ($childIds as $childId) {
            $oxChildArticle = $this->shopgateItemExportHelper->getArticleBase();
            $oxChildArticle->load($childId);
            $children[] = $this->getChildData($oxChildArticle);
        }

        $config = marm_shopgate::getInstance()->getConfig();
        if (
            !$this->item->getParentArticle() && $this->item->oxarticles__oxvarcount->value
            && $config->isVariantParentBuyable()
        ) {
            # if setting "parent products are buyable" is true, export parent item again as child item
            $item                  = clone $this->item;
            $item->sg_act_as_child = true;
            $children[]            = $this->getChildData($item);
        }
        parent::setChildren($children);
    }

    /**
     * @param oxArticle $oxArticle
     */
    public function setParentItem(oxArticle $oxArticle)
    {
        $this->parent = $oxArticle;
    }

    /**
     * @param OxArticle $product
     *
     * @return Shopgate_Model_Export_Product
     */
    protected function getChildData($product)
    {
        $child = new Shopgate_Model_Export_Product($this->shopgateItemExportHelper);
        $child->setItem($product);
        $child->setParentItem($this->item);
        $child->setUseTaxClasses($this->useTaxClasses);
        $child->setIsChild(true);
        $child->setFireMethodsForChildren();
        $child->generateData();

        return $child;
    }

    public function setFireMethodsForChildren()
    {
        $this->fireMethods = array(
            'setAttributes',
            'setDeeplink',
            'setDescription',
            'setImages',
            'setInputs',
            'setInternalOrderInfo',
            'setLastUpdate',
            'setManufacturer',
            'setName',
            'setPrice',
            'setProperties',
            'setRelations',
            'setShipping',
            'setStock',
            'setUid',
            'setVisibility',
            'setIdentifiers',
            'setWeight',
            'setTaxClass',
            'setTaxPercent',
        );
    }

    public function setCurrency()
    {
        parent::setCurrency($this->shopgateItemExportHelper->getCurrency());
    }

    public function setDeeplink()
    {
        parent::setDeeplink($this->shopgateItemExportHelper->getDeeplink($this->item));
    }

    public function setDescription()
    {
        parent::setDescription($this->shopgateItemExportHelper->getDescription($this->item));
    }

    public function setDisplayType()
    {
        parent::setDisplayType(Shopgate_Model_Catalog_Product::DISPLAY_TYPE_DEFAULT);
    }

    public function setIdentifiers()
    {
        $identifiers = array();
        if (!empty($this->item->oxarticles__oxartnum->value)) {
            $identifiers[] = $this->shopgateItemExportHelper->buildIdentifier(
                'SKU',
                $this->item->oxarticles__oxartnum->value
            );
        }
        $ean = $this->shopgateItemExportHelper->getEAN($this->item);
        if (!empty($ean)) {
            $identifiers[] = $this->shopgateItemExportHelper->buildIdentifier('EAN', $ean);
        }
        parent::setIdentifiers($identifiers);
    }

    public function setImages()
    {
        parent::setImages($this->shopgateItemExportHelper->getImages($this->item));
    }

    public function setInputs()
    {
        $inputs = $this->shopgateItemExportHelper->getInputs($this->item);

        $options = $this->shopgateItemExportHelper->getOptions($this->item, $this->isChild);
        foreach ($options as $option) {
            $input = new Shopgate_Model_Catalog_Input();
            $input->setUid($option['id']);
            $input->setLabel($option['label']);
            $input->setType(Shopgate_Model_Catalog_Input::DEFAULT_INPUT_TYPE_SELECT);
            $input->setRequired(true);
            $sgOptions = array();
            foreach ($option['values'] as $value) {
                $sgOption = new Shopgate_Model_Catalog_Option();
                $sgOption->setUid($value['id']);
                $sgOption->setLabel($value['name']);
                if ($this->useTaxClasses) {
                    $sgOption->setAdditionalPrice($value['price'] / (100 + $this->item->getArticleVat()));
                } else {
                    $sgOption->setAdditionalPrice($value['price'] / 100);
                }
                $sgOptions[] = $sgOption;
            }
            $input->setOptions($sgOptions);
            $inputs[] = $input;
        }

        parent::setInputs($inputs);
    }

    public function setInternalOrderInfo()
    {
        parent::setInternalOrderInfo(
            $this->jsonEncode($this->shopgateItemExportHelper->getInternalOrderInfo($this->item))
        );
    }

    public function setLastUpdate()
    {
        parent::setLastUpdate($this->shopgateItemExportHelper->getLastUpdate($this->item));
    }

    public function setManufacturer()
    {
        $uid   = $this->item->oxarticles__oxmanufacturerid->value;
        $title = $this->shopgateItemExportHelper->getgetManufacturerTitle($this->item);

        $manufacturer = new Shopgate_Model_Catalog_Manufacturer();
        $manufacturer->setUid($uid);
        $manufacturer->setTitle($title);
        $manufacturer->setItemNumber($this->shopgateItemExportHelper->getManufacturerItemNumber($this->item));
        parent::setManufacturer($manufacturer);
    }

    public function setName()
    {
        parent::setName($this->shopgateItemExportHelper->getName($this->item));
    }

    public function setPrice()
    {
        $price = new Shopgate_Model_Catalog_Price();
        if ($this->useTaxClasses) {
            $price->setType(Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET);
            $price->setSalePrice($this->shopgateItemExportHelper->getUnitAmountNet($this->item));
            $price->setPrice($this->shopgateItemExportHelper->getOldUnitAmountNet($this->item));
        } else {
            $price->setType(Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_GROSS);
            $price->setSalePrice($this->shopgateItemExportHelper->getUnitAmount($this->item));
            $price->setPrice($this->shopgateItemExportHelper->getOldUnitAmount($this->item));
        }
        $price->setCost($this->shopgateItemExportHelper->getCost($this->item));
        $price->setMsrp($this->shopgateItemExportHelper->getMsrp($this->item, $this->useTaxClasses));
        $price->setTierPricesGroup($this->shopgateItemExportHelper->getTierPrices($this->item));
        $price->setBasePrice($this->shopgateItemExportHelper->getBasicPrice($this->item));

        parent::setPrice($price);
    }

    public function setProperties()
    {
        parent::setProperties($this->shopgateItemExportHelper->getProperties($this->item));
    }

    public function setRelations()
    {
        $relations = array();

        /** @var oxArticleList $crossSellings */
        $crossSellings = $this->item->getCrossSelling();
        if (!empty($crossSellings)) {
            $crossSellingIds = array();
            foreach ($crossSellings as $crossSelling) {
                /** @var oxArticle $crossSelling */
                $crossSellingIds[] = $this->shopgateItemExportHelper->getItemNumber($crossSelling, $this->isChild);
            }
            if (!empty($crossSellingIds)) {
                // setting the type to upsell because crosssell is currently unsupported
                $relations[] =
                    $this->shopgateItemExportHelper->buildRelation(
                        Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_UPSELL,
                        $crossSellingIds
                    );
            }
        }

        $accessories = $this->item->getAccessoires();
        if (!empty($accessories)) {
            $accessoryIds = array();
            foreach ($accessories as $accessory) {
                /** @var oxArticle $accessory */
                $accessoryIds[] = $this->shopgateItemExportHelper->getItemNumber($accessory, $this->isChild);
            }
            if (!empty($accessoryIds)) {
                // setting the type to upsell because relation is currently unsupported
                $relations[] = $this->shopgateItemExportHelper->buildRelation(
                    Shopgate_Model_Catalog_Relation::DEFAULT_RELATION_TYPE_UPSELL,
                    $accessoryIds
                );
            }
        }

        parent::setRelations($relations);
    }

    public function setShipping()
    {
        $shipping = new Shopgate_Model_Catalog_Shipping();
        $shipping->setIsFree($this->shopgateItemExportHelper->getIsFreeShipping($this->item));
        parent::setShipping($shipping);
    }

    public function setStock()
    {
        $config = marm_shopgate::getInstance()->getConfig();
        $stock  = new Shopgate_Model_Catalog_Stock();
        $stock->setIsSaleable($this->shopgateItemExportHelper->getIsSaleable($this->item));
        $stock->setBackorders(
            $this->item->oxarticles__oxstockflag->value == 4
                ? 1
                : 0
        );
        $stock->setUseStock(
            $config->getSysUseStock() && ($this->item->oxarticles__oxstockflag->value == 2
                || $this->item->oxarticles__oxstockflag->value == 3)
                ? 1
                : 0
        );
        $stock->setStockQuantity($this->shopgateItemExportHelper->getStockQuantity($this->item));
        $stock->setMaximumOrderQuantity($this->shopgateItemExportHelper->getMaximumOrderQuantity($this->item));
        $stock->setMinimumOrderQuantity($this->shopgateItemExportHelper->getMinimumOrderQuantity($this->item));
        $stock->setAvailabilityText($this->shopgateItemExportHelper->getAvailabilityText($this->item));
        parent::setStock($stock);
    }

    public function setTags()
    {
        parent::setTags($this->shopgateItemExportHelper->getTags($this->item));
    }

    public function setTaxClass()
    {
        parent::setTaxClass($this->shopgateItemExportHelper->getTaxClass($this->item));
    }

    public function setTaxPercent()
    {
        parent::setTaxPercent($this->shopgateItemExportHelper->getTaxPercent($this->item));
    }

    public function setUid()
    {
        parent::setUid($this->shopgateItemExportHelper->getItemNumber($this->item, $this->isChild));
    }

    public function setVisibility()
    {
        $visibility = new Shopgate_Model_Catalog_Visibility();
        $visibility->setMarketplace($this->shopgateItemExportHelper->getIsMarketplace($this->item));
        parent::setVisibility($visibility);
    }

    public function setWeight()
    {
        parent::setWeight($this->shopgateItemExportHelper->getWeight($this->item));
    }

    public function setWeightUnit()
    {
        parent::setWeightUnit($this->shopgateItemExportHelper->getWeightUnit($this->item));
    }

    ###############################################################################################
    ## Csv
    ###############################################################################################

    /**
     * @return array
     */
    public function asCsv()
    {
        $children = $this->getChildren();
        $result   = array(
            'active_status'            => $this->shopgateItemExportHelper->getActiveStatus($this->item),
            'available_text'           => $this->shopgateItemExportHelper->getAvailabilityText($this->item),
            'basic_price'              => $this->shopgateItemExportHelper->getBasicPrice($this->item),
            'category_numbers'         => $this->shopgateItemExportHelper->getCategoryNumbers(
                $this->getCategoryPaths()
            ),
            'currency'                 => $this->shopgateItemExportHelper->getCurrency(),
            'description'              => $this->shopgateItemExportHelper->getDescription($this->item),
            'ean'                      => $this->shopgateItemExportHelper->getEAN($this->item),
            'has_children'             => empty($children)
                ? 0
                : 1,
            'internal_order_info'      => $this->jsonEncode(
                $this->shopgateItemExportHelper->getInternalOrderInfo($this->item)
            ),
            'is_available'             => $this->shopgateItemExportHelper->getIsSaleable($this->item),
            'is_free_shipping'         => $this->shopgateItemExportHelper->getIsFreeShipping($this->item)
                ? 1
                : 0,
            'is_highlight'             => $this->shopgateItemExportHelper->getIsHighlight($this->item)
                ? 1
                : 0,
            'item_name'                => $this->shopgateItemExportHelper->getName($this->item),
            'item_number'              => $this->shopgateItemExportHelper->getItemNumber($this->item, $this->isChild),
            'item_number_public'       => $this->shopgateItemExportHelper->getItemNumberPublic($this->item),
            'last_update'              => $this->shopgateItemExportHelper->getLastUpdate($this->item),
            'manufacturer'             => $this->shopgateItemExportHelper->getgetManufacturerTitle($this->item),
            'manufacturer_item_number' => $this->shopgateItemExportHelper->getManufacturerItemNumber($this->item),
            'marketplace'              => $this->shopgateItemExportHelper->getIsMarketplace($this->item)
                ? 1
                : 0,
            'maximum_order_quantity'   => $this->shopgateItemExportHelper->getMaximumOrderQuantity($this->item),
            'minimum_order_quantity'   => $this->shopgateItemExportHelper->getMinimumOrderQuantity($this->item),
            'msrp'                     => $this->shopgateItemExportHelper->getMsrp($this->item, $this->useTaxClasses),
            'parent_item_number'       => $this->shopgateItemExportHelper->getParentItemNumber(
                $this->item,
                $this->isChild
            ),
            'properties'               => $this->shopgateItemExportHelper->getPropertiesCsv($this->item),
            'stock_quantity'           => $this->shopgateItemExportHelper->getStockQuantity($this->item),
            'tags'                     => $this->shopgateItemExportHelper->getTagsCsv($this->item),
            'use_stock'                => $this->shopgateItemExportHelper->getUseStock($this->item),
            'url_deeplink'             => $this->shopgateItemExportHelper->getDeeplink($this->item),
            'urls_images'              => $this->shopgateItemExportHelper->getImageUrls($this->getImages()),
            'weight'                   => $this->shopgateItemExportHelper->getWeight($this->item),
            'weight_unit'              => $this->shopgateItemExportHelper->getWeightUnit($this->item),
        );

        if ($this->useTaxClasses) {
            $result['old_unit_amount_net'] = $this->shopgateItemExportHelper->getOldUnitAmountNet($this->item);
            $result['unit_amount_net']     = $this->shopgateItemExportHelper->getUnitAmountNet($this->item);
            $result['tax_class']           = $this->shopgateItemExportHelper->getTaxClass($this->item);
        } else {
            $result['old_unit_amount'] = $this->shopgateItemExportHelper->getOldUnitAmount($this->item);
            $result['unit_amount']     = $this->shopgateItemExportHelper->getUnitAmount($this->item);
            $result['tax_percent']     = $this->shopgateItemExportHelper->getTaxPercent($this->item);
        }

        # Attributes
        if ($this->isChild) {
            /** @var Shopgate_Model_Catalog_Attribute[] $attributes */
            $attributes = $this->getAttributes();
            $i          = 0;
            foreach ($attributes as $attribute) {
                $i++;
                $result["attribute_" . $attribute->getGroupUid()] = trim($attribute->getLabel());
            }
        } else {
            /** @var Shopgate_Model_Catalog_AttributeGroup[] $attributeGroups */
            $attributeGroups = $this->getAttributeGroups();
            $i               = 0;
            foreach ($attributeGroups as $group) {
                $i++;
                $result["attribute_" . $group->getUid()] = trim($group->getLabel());
            }
        }

        # Inputs
        $inputs                     = $this->shopgateItemExportHelper->getInputs($this->item);
        $result['has_input_fields'] = (count($inputs) > 0)
            ? 1
            : 0;
        if (count($inputs) > 0) {
            $i = 1;
            foreach ($inputs as $input) {
                $result["input_field_{$i}_label"]    = $input->getLabel();
                $result["input_field_{$i}_type"]     = $input->getType();
                $result["input_field_{$i}_required"] = 1;
                $i++;
            }
        }

        # Options
        $options               = $this->shopgateItemExportHelper->getOptions($this->item, $this->isChild);
        $result['has_options'] = (count($options) > 0)
            ? 1
            : 0;
        foreach ($options as $i => $option) {
            $i++;
            $values = array();
            foreach ($option['values'] as $value) {
                $values[] = $value['id'] . '=' . $value['name'] . '=>' . $value['price'];
            }
            $result["option_{$i}"]        = $option['label'];
            $result["option_{$i}_values"] = implode(self::MULTI_SEPARATOR, $values);
        }

        return $result;
    }
}
