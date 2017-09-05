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

/**
 * Helper class used by get_customer
 */
class ShopgateCustomerExportHelper
{
    /** @var ShopgateConfigOxid */
    private $config;

    /** @var oxUser */
    private $oxUser;

    /**
     * @param ShopgateConfigOxid $config
     */
    public function __construct(ShopgateConfigOxid $config)
    {
        $this->config = $config;
    }

    /**
     * @param oxUser $oxUser
     *
     * @return ShopgateContainer|ShopgateCustomer
     */
    public function buildExternalCustomer(oxUser $oxUser)
    {
        $this->oxUser = $oxUser;

        $customer = new ShopgateCustomer();
        $customer->setCustomerId($this->getCustomerId());
        $customer->setCustomerToken($this->getCustomerToken());
        $customer->setCustomerNumber($this->getCustomerNumber());
        $customer->setFirstName($this->getFirstName());
        $customer->setLastName($this->getLastName());
        $customer->setMail($this->getMail());
        $customer->setPhone($this->getPhone());
        $customer->setMobile($this->getMobile());
        $customer->setGender($this->getGender());
        $customer->setNewsletterSubscription($this->getNewsletterSubscription());
        $customer->setCustomerGroups($this->getCustomerGroups());
        $customer->setAddresses($this->getAddresses());

        return $customer->utf8Encode($this->config->getEncoding());
    }

    /**
     * @return string
     */
    private function getCustomerId()
    {
        return $this->oxUser->oxuser__oxid->value;
    }

    /**
     * @return string
     */
    private function getCustomerToken()
    {
        return $this->oxUser->oxuser__oxid->value;
    }

    /**
     * @return string
     */
    private function getCustomerNumber()
    {
        return $this->oxUser->oxuser__oxcustnr->value;
    }

    /**
     * @return string
     */
    private function getFirstName()
    {
        return $this->oxUser->oxuser__oxfname->value;
    }

    /**
     * @return string
     */
    private function getLastName()
    {
        return $this->oxUser->oxuser__oxlname->value;
    }

    /**
     * @return string
     */
    private function getMail()
    {
        return $this->oxUser->oxuser__oxusername->value;
    }

    /**
     * @return string
     */
    private function getPhone()
    {
        return $this->oxUser->oxuser__oxfon->value;
    }

    /**
     * @return string
     */
    private function getMobile()
    {
        return $this->oxUser->oxuser__oxmobfon->value;
    }

    /**
     * @return string
     */
    private function getGender()
    {
        return marm_shopgate::getGenderByOxidSalutation($this->oxUser->oxuser__oxsal->value);
    }

    /**
     * @return bool
     */
    private function getNewsletterSubscription()
    {
        return $this->oxUser->getNewsSubscription()->oxnewssubscribed__oxdboptin->value;
    }

    /**
     * @return ShopgateCustomerGroup[]
     */
    private function getCustomerGroups()
    {
        $customerGroups = array();
        foreach (marm_shopgate::getUserGroupsByUser($this->oxUser) as $oxGroup) {
            $group = new ShopgateCustomerGroup();
            $group->setId($oxGroup->oxgroups__oxid->value);
            $group->setName($oxGroup->oxgroups__oxtitle->value);
            $customerGroups[$oxGroup->oxgroups__oxid->value] = $group;
        }

        return $customerGroups;
    }

    /**
     * @return ShopgateAddress[]
     */
    private function getAddresses()
    {
        $addresses = array();

        // the address data from oxuser are the invoice address
        // this address can also act as a delivery address
        $addresses[] = $this->buildShopgateAddress($this->oxUser);
        foreach ($this->oxUser->getUserAddresses() as $oAddress) {
            $addresses[] = $this->buildShopgateAddress($oAddress);
        }

        return $addresses;
    }

    /**
     * create ShopgateAddress from oxaddress or oxuser
     *
     * @param oxUser|oxAddress $oxAddress
     *
     * @return ShopgateAddress
     */
    private function buildShopgateAddress($oxAddress)
    {
        $tableName = $oxAddress->getCoreTableName();
        $country   = marm_shopgate::getCountryCodeByOxid($oxAddress->{"{$tableName}__oxcountryid"}->value);
        $state     = marm_shopgate::getStateCodeByOxid($oxAddress->{"{$tableName}__oxstateid"}->value);

        $result = new ShopgateAddress();

        $result->setId($oxAddress->{"{$tableName}__oxid"}->value);
        $result->setIsInvoiceAddress(($tableName == "oxuser"));
        $result->setIsDeliveryAddress(true);

        $result->setFirstName($oxAddress->{"{$tableName}__oxfname"}->value);
        $result->setLastName($oxAddress->{"{$tableName}__oxlname"}->value);
        $result->setCompany($oxAddress->{"{$tableName}__oxcompany"}->value);
        $result->setStreet1(
            $oxAddress->{"{$tableName}__oxstreet"}->value . ' ' . $oxAddress->{"{$tableName}__oxstreetnr"}->value
        );
        $result->setStreet2($oxAddress->{"{$tableName}__oxaddinfo"}->value);
        $result->setCity($oxAddress->{"{$tableName}__oxcity"}->value);
        $result->setZipcode($oxAddress->{"{$tableName}__oxzip"}->value);
        $result->setCountry($country);
        $result->setState(
            (!empty($country) && !empty($state))
                ? "$country-$state"
                : ''
        );
        $result->setGender(marm_shopgate::getGenderByOxidSalutation($oxAddress->{"{$tableName}__oxsal"}->value));
        $result->setBirthday($oxAddress->{"{$tableName}__oxbirthdate"}->value);
        $result->setPhone($oxAddress->{"{$tableName}__oxfon"}->value);
        $result->setMobile($oxAddress->{"{$tableName}__oxmobfon"}->value);

        return $result;
    }
}
