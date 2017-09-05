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

class ShopgateUnknownOxidConfigFields
{
    const OXID_VARTYPE_STRING = 'str';

    private static $oxidConfigUnknownField = 'unknown_fields';

    /** @var array */
    private $unknownOxidConfigFields;

    /** @var \oxConfig */
    private $oxidConfig;

    /** @var ShopgateConfigOxid */
    private $shopgateConfig;

    /** @var marm_shopgate */
    private $marmShopgate;

    /**
     * @param ShopgateConfigOxid $shopgateConfigOxid
     * @param \oxConfig          $oxConfig
     * @param marm_shopgate      $marmShopgate
     */
    public function __construct(ShopgateConfigOxid $shopgateConfigOxid, $oxConfig, $marmShopgate)
    {
        $this->unknownOxidConfigFields = array();
        $this->shopgateConfig          = $shopgateConfigOxid;
        $this->oxidConfig              = $oxConfig;
        $this->marmShopgate            = $marmShopgate;
    }

    /**
     * @param array $unknownOxidConfigFields
     */
    public function save(array $unknownOxidConfigFields)
    {
        $unknownOxidConfigFields = $this->setAlreadyExistingUnknownFields($unknownOxidConfigFields);

        $this->oxidConfig->saveShopConfVar(
            self::OXID_VARTYPE_STRING,
            $this->marmShopgate->getOxidConfigKey(self::$oxidConfigUnknownField),
            $this->shopgateConfig->jsonEncode($unknownOxidConfigFields)
        );
    }

    /**
     * @post $this->shopgateConfig contains all "unknown fields" as object variables
     */
    public function load()
    {
        $unknownOxidConfigFieldsJsonString = $this->oxidConfig->getConfigParam(
            $this->marmShopgate->getOxidConfigKey(self::$oxidConfigUnknownField)
        );

        if (empty($unknownOxidConfigFieldsJsonString)) {
            return;
        }

        $unknownOxidConfigurationFields = $this->shopgateConfig->jsonDecode($unknownOxidConfigFieldsJsonString, true);
        foreach ($unknownOxidConfigurationFields as $field => $value) {
            $setter     = $this->shopgateConfig->camelize($field);
            $methodName = 'set' . $setter;
            if (method_exists($this->shopgateConfig, $methodName)) {
                $this->shopgateConfig->$methodName($value);
            }
        }

        $this->unknownOxidConfigFields = array_keys($unknownOxidConfigurationFields);
    }

    /**
     * @param array $unknownOxidConfigFields
     *
     * @return array
     */
    private function setAlreadyExistingUnknownFields(array $unknownOxidConfigFields)
    {
        foreach ($this->unknownOxidConfigFields as $alreadyExistingOxidConfigUnknownField) {
            if (isset($unknownOxidConfigFields[$alreadyExistingOxidConfigUnknownField])) {
                continue;
            }

            $getter                                                          = $this->shopgateConfig->camelize(
                $alreadyExistingOxidConfigUnknownField
            );
            $unknownOxidConfigFields[$alreadyExistingOxidConfigUnknownField] = $this->shopgateConfig->{'get' . $getter}(
            );
        }

        return $unknownOxidConfigFields;
    }
}
