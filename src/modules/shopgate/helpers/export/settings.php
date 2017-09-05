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

class ShopgateSettingsExportHelper extends ShopgateObject
{
    /** @var ShopgateConfigOxid */
    private $config;

    /**
     * ShopgateSettingsExportHelper constructor.
     *
     * @param ShopgateConfigOxid $config
     */
    public function __construct(ShopgateConfigOxid $config)
    {
        $this->config = $config;
    }

    /**
     * all active countries
     *
     * @return array
     */
    public function getAllowedAddressCountries()
    {
        /** @var oxCountry $oxCountry */
        $oxCountry = oxNew('oxCountry');
        $sql       = "SELECT OXISOALPHA2 FROM {$oxCountry->getViewName()} WHERE {$oxCountry->getSqlActiveSnippet()}";
        $countries = marm_shopgate::dbGetAll($sql);

        $result = array();
        foreach ($countries as $country) {
            $result[] = array(
                'country' => $country['OXISOALPHA2'],
                'state'   => array('all'),
            );
        }

        return $result;
    }

    /**
     * all active countries with an active delivery service
     *
     * @return array
     */
    public function getAllowedShippingCountries()
    {
        /** @var oxCountry $oxCountry */
        $oxCountry = oxNew('oxCountry');
        /** @var oxDelivery $oxDelivery */
        $oxDelivery = oxNew('oxDelivery');

        $sql       = "SELECT OXISOALPHA2
				FROM {$oxCountry->getViewName()}
				WHERE {$oxCountry->getSqlActiveSnippet()}
				AND OXID IN (
					SELECT OXOBJECTID FROM oxobject2delivery WHERE OXDELIVERYID IN (
						SELECT OXID FROM {$oxDelivery->getViewName()} WHERE {$oxDelivery->getSqlActiveSnippet()}
					)
				)";
        $countries = marm_shopgate::dbGetAll($sql);

        $result = array();
        foreach ($countries as $country) {
            $result[] = array(
                'country' => $country['OXISOALPHA2'],
                'state'   => array('all'),
            );
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getCustomerGroups()
    {
        /** @var oxGroups $oxGroups */
        $oxGroups = oxNew('oxGroups');

        $ids = marm_shopgate::dbGetAll(
            "SELECT `OXID`
                   FROM {$oxGroups->getViewName()}
                   WHERE {$oxGroups->getSqlActiveSnippet()}
                   ORDER BY `OXID`"
        );

        $result = array();
        foreach ($ids as $id) {
            $id       = array_shift($id);
            $oxGroups = oxNew('oxGroups');
            $oxGroups->load($id);
            $group    = array(
                'id'         => $id,
                'name'       => $this->stringToUtf8($oxGroups->oxgroups__oxtitle->value, $this->config->getEncoding()),
                'is_default' => 0,
            );
            $result[] = $group;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getTaxSettings()
    {
        $defaultTax = marm_shopgate::getOxConfig()->getConfigParam('dDefaultVAT');
        /** @var oxArticle $oxArticle */
        $oxArticle = oxNew('oxArticle');

        // find all used taxes in articles
        $qry   = "SELECT DISTINCT a.oxvat AS OXVAT FROM (
					SELECT 0 AS oxvat UNION
					SELECT DISTINCT ROUND(oxvat, 4) FROM {$oxArticle->getViewName()} WHERE oxvat IS NOT NULL GROUP BY oxvat
				) a";
        $aVats = marm_shopgate::dbGetAll($qry);

        if (!empty($defaultTax)) {
            $aVats[] = array('OXVAT' => $defaultTax);
        }

        $result                         = array();
        $result['product_tax_classes']  = $this->getProductTaxClasses($aVats);
        $result['customer_tax_classes'] = $this->getCustomerTaxClasses();
        $result['tax_rates']            = $this->getTaxRates($aVats);
        $result['tax_rules']            = $this->getTaxRules($result['tax_rates']);

        return $result;
    }

    /**
     * export all taxes from shop with prefix 'tax_' as tax class
     * Examples: tax_19, tax_7, tax_0
     *
     * @param array $aVats
     *
     * @return array
     */
    protected function getProductTaxClasses(array $aVats)
    {
        $result = array();
        foreach ($aVats as $vat) {
            $result[] = array('key' => "tax_{$vat["OXVAT"]}");
        }

        return $result;
    }

    /**
     * oxid has no customer tax classes. export only one class as "default"
     *
     * @return array
     */
    protected function getCustomerTaxClasses()
    {
        return array(
            array('key' => 'default'),
        );
    }

    /**
     * Export rates for each country with in format 'rate_XX_Y'
     * XX is iso2 country code and Y is the tax rate
     * If tax is free for a country then the rate is export as rate_XX_0
     *
     * Example: rate_DE_19, rate_CH_0
     *
     * @param array $aVats
     *
     * @return array
     */
    protected function getTaxRates(array $aVats)
    {
        /** @var oxCountry $oxCountry */
        $oxCountry        = oxNew('oxCountry');
        $sSql             = "SELECT * FROM {$oxCountry->getViewName()} WHERE {$oxCountry->getSqlActiveSnippet()}";
        $countries        = marm_shopgate::dbGetAll($sSql);
        $homeCountryCodes = $this->getHomeCountryCodes();
        $result           = array();
        $keys             = array();
        foreach ($countries as $country) {
            if (
                !empty($country['OXISOALPHA2']) && (
                    !empty($country['OXVATSTATUS']) || in_array(
                        $country['OXISOALPHA2'],
                        $homeCountryCodes
                    )
                )
            ) {
                foreach ($aVats as $vat) {
                    // prevent the same key from being duplicated twice
                    if (isset($keys["rate_{$country['OXISOALPHA2']}_{$vat['OXVAT']}"])) {
                        continue;
                    }

                    $keys["rate_{$country['OXISOALPHA2']}_{$vat['OXVAT']}"] = true;
                    $result[]                                               = array(
                        'key'          => "rate_{$country['OXISOALPHA2']}_{$vat['OXVAT']}",
                        'display_name' => "{$country['OXISOALPHA2']} {$vat['OXVAT']}%",
                        'tax_percent'  => $vat['OXVAT'],
                        'country'      => $country['OXISOALPHA2'],
                        'state'        => '',
                        'zipcode_type' => 'all',
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Export rules for each rate and class combination
     *
     * @param array $taxRates
     *
     * @return array
     */
    protected function getTaxRules(array $taxRates)
    {
        $result = array();

        foreach ($taxRates as $tax) {
            if (empty($result[$tax['tax_percent']]['name'])) {
                $result[$tax['tax_percent']]['name'] = $tax['tax_percent'] . "%-" . $tax['country'];
            } else {
                $result[$tax['tax_percent']]['name'] .= '-' . $tax['country'];
            }

            $result[$tax['tax_percent']]['product_tax_classes']  = array(array('key' => "tax_{$tax['tax_percent']}"));
            $result[$tax['tax_percent']]['priority']             = 0;
            $result[$tax['tax_percent']]['customer_tax_classes'] = array(array('key' => 'default'));

            if (!isset($result[$tax['tax_percent']]['tax_rates'])) {
                $result[$tax['tax_percent']]['tax_rates'] = array();
            }
            $result[$tax['tax_percent']]['tax_rates'][] = array('key' => $tax['key']);
        }

        return array_values($result);
    }

    /**
     * returns all active payment methods
     *
     * @return ShopgatePaymentMethod[]
     */
    public function getPaymentMethods()
    {
        /** @var oxPayment $oxPayment */
        $oxPayment = oxNew('oxPayment');
        $sql       = "SELECT `OXID` FROM {$oxPayment->getViewName()} WHERE {$oxPayment->getSqlActiveSnippet()}";
        $methods   = marm_shopgate::dbGetAll($sql);

        $result = array();
        foreach ($methods as $oxidMethod) {
            $result[] = array(
                'id' => $oxidMethod['OXID'],
            );
        }

        return $result;
    }

    /**
     * returns the ISO2 Codes of defined home countries
     *
     * @return string[] $homeCountryCodes
     */
    private function getHomeCountryCodes()
    {
        $homeCountryCodes = array();
        $homeCountryIds   = marm_shopgate::getOxConfig()->getConfigParam('aHomeCountry');
        if (is_array($homeCountryIds)) {
            foreach ($homeCountryIds as $homeCountryId) {
                /** @var oxCountry $oxCountry */
                $oxCountry = oxNew('oxCountry');
                if ($oxCountry->load($homeCountryId)) {
                    $homeCountryCodes[] = $oxCountry->oxcountry__oxisoalpha2->value;
                }
            }
        }

        return $homeCountryCodes;
    }
}
