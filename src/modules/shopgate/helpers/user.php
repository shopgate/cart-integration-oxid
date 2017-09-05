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
 * Class ShopgateUserHelper
 */
class ShopgateUserHelper extends ShopgateObject
{
    const OXID_GENDER_MALE   = 'MR';
    const OXID_GENDER_FEMALE = 'MRS';

    /** @var array | null */
    private $states = null;

    /** @var bool */
    private $guestAccountCreated = false;

    /**
     * returns user oxid if it exist by email
     *
     * @param ShopgateCartBase $shopgateOrder
     *
     * @return null|oxuser
     */
    public function getOxidUserByEmail(ShopgateCartBase $shopgateOrder)
    {
        $this->log(__FUNCTION__ . ' invoked', ShopgateLogger::LOGTYPE_DEBUG);

        /** @var oxUser $oxUser */
        $oxUser   = oxNew('oxUser');
        $oxUserId = $this->getIdByUserName($shopgateOrder->getMail());

        // If user not found by mail, try to fetch by external user id
        if (!$oxUserId) {
            $oxUserId = $shopgateOrder->getExternalCustomerId();
        }

        if (!empty($oxUserId)) {
            $this->checkUserForMissingShopId($oxUserId);
        }

        $this->log("> UserID - {$oxUserId}", ShopgateLogger::LOGTYPE_DEBUG);
        if (!$oxUserId || !$oxUser->load($oxUserId)) {
            $this->log("> Cannot load user > create a new guest account", ShopgateLogger::LOGTYPE_DEBUG);
            $this->guestAccountCreated = true;

            if (_SHOPGATE_ACTION == ShopgatePluginOxid::ACTION_CHECK_CART) {
                // Create a dummy user for check_cart (gets deleted when finished)
                $date         = date('ymd');
                $randomNumber = rand(100000, 999999);
                $shopgateOrder->setMail("info+$date-$randomNumber@shopgate.com");
                $invoiceAddress = new ShopgateAddress();
                $invoiceAddress->setLastName(shopgate_oxuser::CHECK_CART_USERNAME);
                if ($shopgateOrder->getDeliveryAddress()) {
                    $invoiceAddress->setCountry($shopgateOrder->getDeliveryAddress()->getCountry());
                } else {
                    $country = $this->getDefaultDeliveryCountryIsoCode();
                    if (strlen($country) == 2) {
                        $invoiceAddress->setCountry($country);
                    }
                }
                $shopgateOrder->setInvoiceAddress($invoiceAddress);
            }

            $oxUser                     = oxNew('oxUser');
            $oxUser->oxuser__oxusername = new oxField($shopgateOrder->getMail(), oxField::T_RAW);
            $oxUser->oxuser__oxactive   = new oxField(1, oxField::T_RAW);
            $oxUser->oxuser__oxshopid   = new oxField(marm_shopgate::getOxConfig()->getShopId(), oxField::T_RAW);
            $oxUser->addToGroup('oxidnotyetordered');

            $oxUser = $this->modifyUserForOrder($oxUser, $shopgateOrder);
        } elseif ($shopgateOrder->getDeliveryAddress()) {
            $oxUser = $this->modifyUserForOrder($oxUser, $shopgateOrder);
        }

        $oxUser->save();

        // Assign customer group (needed for check_cart)
        $shopgate_oxuser = new shopgate_oxuser();
        $shopgate_oxuser->load($oxUser->getId());
        $shopgate_oxuser->setAutoGroups($oxUser->oxuser__oxcountryid);

        return $oxUser;
    }

    /**
     * @param oxUser           $oxUser
     * @param ShopgateCartBase $shopgateOrder
     *
     * @return oxUser
     */
    protected function modifyUserForOrder(oxUser $oxUser, ShopgateCartBase $shopgateOrder)
    {
        $this->log(__FUNCTION__ . ' invoked', ShopgateLogger::LOGTYPE_DEBUG);

        $oxUser = $this->setOxidUserInvoiceData($oxUser, $shopgateOrder);
        $oxUser->save();

        $deliveryAddress = $shopgateOrder->getDeliveryAddress();
        if (!$deliveryAddress) {
            return $oxUser;
        }

        $oxDeliveryAddress = $this->createDeliveryAddress($oxUser->getId(), $deliveryAddress);

        $addressCompareFields = array(
            'oxfname',
            'oxlname',
            'oxcompany',
            'oxstreet',
            'oxstreetnr',
            'oxcity',
            'oxcountryid',
        );
        foreach ($deliveryAddress->getCustomFields() as $customField) {
            $fieldName = $customField->getInternalFieldName();
            if (isset($oxDeliveryAddress->{"oxaddress__$fieldName"})) {
                $addressCompareFields[] = $fieldName;
            }
        }

        if ($this->compareAddresses($oxUser, $oxDeliveryAddress, $addressCompareFields)) {
            return $oxUser;
        }

        // Search for a oxaddress which the current data
        /** @var oxAddress[] $oxAddresses */
        $oxAddresses = $oxUser->getUserAddresses();

        $foundAddress = false;
        foreach ($oxAddresses as $oxAddress) {
            if ($this->compareAddresses($oxDeliveryAddress, $oxAddress, $addressCompareFields)) {
                // the addresses are identical
                /** @noinspection PhpUndefinedMethodInspection */
                marm_shopgate::setSessionVar('deladrid', $oxAddress->getId());
                if (method_exists('oxUser', 'setSelectedAddressId')) {
                    $oxUser->setSelectedAddressId($oxAddress->getId());
                }
                $foundAddress = true;
                break;
            }
        }

        // If no address was found we should create a new address entry
        if (!$foundAddress) {
            $oxDeliveryAddress->save();

            /** @noinspection PhpUndefinedMethodInspection */
            marm_shopgate::setSessionVar('deladrid', $oxDeliveryAddress->getId());
            if (method_exists('oxUser', 'setSelectedAddressId')) {
                $oxUser->setSelectedAddressId($oxDeliveryAddress->getId());
            }
        }

        $oxUser->save();

        return $oxUser;
    }

    /**
     * @param oxUser|oxAddress $address1
     * @param oxAddress        $address2
     * @param array            $fields
     *
     * @return bool true if addresses are identical
     */
    private function compareAddresses($address1, $address2, $fields)
    {
        $class1 = ($address1 instanceof oxUser)
            ? 'oxuser'
            : 'oxaddress';
        $class2 = ($address2 instanceof oxUser)
            ? 'oxuser'
            : 'oxaddress';
        foreach ($fields as $field) {
            if ($address1->{$class1 . '__' . $field}->value != $address2->{$class2 . '__' . $field}->value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string          $userId
     * @param ShopgateAddress $deliveryAddress
     *
     * @return oxAddress
     */
    public function createDeliveryAddress($userId, ShopgateAddress $deliveryAddress)
    {
        /** @var oxAddress $oxAddress */
        $oxAddress                         = oxNew('oxAddress');
        $oxAddress->oxaddress__oxuserid    = new oxField($userId, oxField::T_RAW);
        $oxAddress->oxaddress__oxcompany   = new oxField($deliveryAddress->getCompany(), oxField::T_RAW);
        $oxAddress->oxaddress__oxfname     = new oxField($deliveryAddress->getFirstName(), oxField::T_RAW);
        $oxAddress->oxaddress__oxlname     = new oxField($deliveryAddress->getLastName(), oxField::T_RAW);
        $oxAddress->oxaddress__oxstreet    = new oxField($deliveryAddress->getStreetName1(), oxField::T_RAW);
        $oxAddress->oxaddress__oxstreetnr  = new oxField($deliveryAddress->getStreetNumber1(), oxField::T_RAW);
        $oxAddress->oxaddress__oxaddinfo   = new oxField($deliveryAddress->getStreet2(), oxField::T_RAW);
        $oxAddress->oxaddress__oxcity      = new oxField($deliveryAddress->getCity(), oxField::T_RAW);
        $oxAddress->oxaddress__oxcountry   = new oxField(
            $this->getCountryName($deliveryAddress->getCountry()),
            oxField::T_RAW
        );
        $oxAddress->oxaddress__oxcountryid = new oxField(
            $this->getCountryOxid($deliveryAddress->getCountry()),
            oxField::T_RAW
        );
        $oxAddress->oxaddress__oxstateid   = new oxField(
            $this->getOxStateByIso($deliveryAddress->getState()),
            oxField::T_RAW
        );
        $oxAddress->oxaddress__oxzip       = new oxField($deliveryAddress->getZipcode(), oxField::T_RAW);
        $oxAddress->oxaddress__oxfon       = new oxField($deliveryAddress->getPhone());
        $oxAddress->oxaddress__oxsal       = new oxField(
            $this->getOxidGender($deliveryAddress->getGender()),
            oxField::T_RAW
        );

        $customFields = $deliveryAddress->getCustomFields();
        foreach ($customFields as $customField) {
            $fieldName = $customField->getInternalFieldName();
            if (isset($oxAddress->{"oxaddress__$fieldName"})) {
                $oxAddress->{"oxaddress__$fieldName"}->value = $customField->getValue();
            }
        }

        return $oxAddress;
    }

    /**
     * @param string $userName
     *
     * @return null|string
     */
    public function getIdByUserName($userName)
    {
        if (empty($userName)) {
            return null;
        }
        $oDb = marm_shopgate::getDb();
        $sQ  = "SELECT `oxid` FROM `oxuser` WHERE `oxusername` = " . $oDb->quote($userName);
        if (!marm_shopgate::getOxConfig()->getConfigParam('blMallUsers')) {
            $sQ .= " AND `oxshopid` = " . $oDb->quote(marm_shopgate::getOxConfig()->getShopId());
        }

        return marm_shopgate::dbGetOne($sQ);
    }

    /**
     * @return null|string
     */
    private function getDefaultDeliveryCountryIsoCode()
    {
        $homeCountry = marm_shopgate::getOxConfig()->getConfigParam('aHomeCountry');
        if (marm_shopgate::getOxConfig()->getConfigParam('blCalculateDelCostIfNotLoggedIn') && is_array($homeCountry)) {
            $countryId = current($homeCountry);
            /** @var oxCountry $oxCountry */
            $oxCountry = oxNew('oxCountry');
            if ($oxCountry->load($countryId)) {
                return $oxCountry->oxcountry__oxisoalpha2->value;
            }
        }

        return null;
    }

    /**
     * Checks whether the user with the given ID has a shopId. If not, sets it.
     *
     * @param string $oxUserId
     */
    private function checkUserForMissingShopId($oxUserId)
    {
        if (marm_shopgate::getOxConfig()->getConfigParam('blMallUsers')) {
            // This flag means that users can access all subshops and hence have no shopId.
            return;
        }
        $shopId = marm_shopgate::dbGetOne("SELECT oxshopid FROM oxuser WHERE oxid = '$oxUserId'");
        if (!empty($shopId)) {
            // User already has a shopId
            return;
        }
        $shopIds = marm_shopgate::dbGetAll("SELECT oxid FROM oxshops;");
        if (count($shopIds) > 1) {
            // Multiple shopIds exist --> We can't be 100% sure which one to set.
            // (Affects only Oxid EE shops with blMallUsers == false)
            return;
        }

        $shopId = marm_shopgate::getOxConfig()->getShopId();
        marm_shopgate::dbExecute("UPDATE oxuser SET oxshopid = '$shopId' WHERE oxid = '$oxUserId'");
    }

    /**
     * @param oxUser           $oxUser
     * @param ShopgateCartBase $shopgateOrder
     *
     * @return oxUser
     */
    public function setOxidUserInvoiceData(oxUser $oxUser, ShopgateCartBase $shopgateOrder = null)
    {
        $this->log(__FUNCTION__ . ' invoked', ShopgateLogger::LOGTYPE_DEBUG);

        $invoiceAddress = $shopgateOrder->getInvoiceAddress();
        if (!$invoiceAddress) {
            return $oxUser;
        }

        $phone = $invoiceAddress->getPhone();
        if (empty($phone)) {
            $phone = $shopgateOrder->getPhone();
        }
        $mobile = $invoiceAddress->getMobile();
        if (empty($mobile)) {
            $mobile = $shopgateOrder->getMobile();
        }

        // ATTENTION omitting empty fields (oxustid, oxfax) leads to a fatal error
        $oxUser->oxuser__oxustid     = new oxField('', oxField::T_RAW);
        $oxUser->oxuser__oxcompany   = new oxField($invoiceAddress->getCompany(), oxField::T_RAW);
        $oxUser->oxuser__oxfname     = new oxField($invoiceAddress->getFirstName(), oxField::T_RAW);
        $oxUser->oxuser__oxlname     = new oxField($invoiceAddress->getLastName(), oxField::T_RAW);
        $oxUser->oxuser__oxstreet    = new oxField($invoiceAddress->getStreetName1(), oxField::T_RAW);
        $oxUser->oxuser__oxstreetnr  = new oxField($invoiceAddress->getStreetNumber1(), oxField::T_RAW);
        $oxUser->oxuser__oxaddinfo   = new oxField($invoiceAddress->getStreet2(), oxField::T_RAW);
        $oxUser->oxuser__oxcity      = new oxField($invoiceAddress->getCity(), oxField::T_RAW);
        $oxUser->oxuser__oxcountryid = new oxField(
            $this->getCountryOxid($invoiceAddress->getCountry()), oxField::T_RAW
        );
        $oxUser->oxuser__oxstateid   = new oxField($this->getOxStateByIso($invoiceAddress->getState()), oxField::T_RAW);
        $oxUser->oxuser__oxzip       = new oxField($invoiceAddress->getZipcode(), oxField::T_RAW);
        $oxUser->oxuser__oxfon       = new oxField($phone, oxField::T_RAW);
        $oxUser->oxuser__oxmobfon    = new oxField($mobile, oxField::T_RAW);
        $oxUser->oxuser__oxbirthdate = new oxField($invoiceAddress->getBirthday(), oxField::T_RAW);
        $oxUser->oxuser__oxsal       = new oxField($this->getOxidGender($invoiceAddress->getGender()), oxField::T_RAW);
        $oxUser->oxuser__oxfax       = new oxField('', oxField::T_RAW);

        $customFields = $invoiceAddress->getCustomFields();
        foreach ($customFields as $customField) {
            $fieldName = $customField->getInternalFieldName();
            if (isset($oxUser->{"oxuser__$fieldName"})) {
                $oxUser->{"oxuser__$fieldName"}->value = $customField->getValue();
            }
        }

        return $oxUser;
    }

    /**
     * searches for country oxid by its ISO number or title
     *
     * @param string $sShopgateCountryId
     *
     * @return null|string
     */
    public function getCountryOxid($sShopgateCountryId)
    {
        return marm_shopgate::dbGetOne("SELECT OXID FROM oxcountry WHERE OXISOALPHA2 = ?", array($sShopgateCountryId));
    }

    /**
     * @param string $sShopgateCountryId
     *
     * @return null|string
     */
    public function getCountryName($sShopgateCountryId)
    {
        return marm_shopgate::dbGetOne(
            "SELECT OXTITLE FROM oxcountry WHERE OXISOALPHA2 = ?",
            array($sShopgateCountryId)
        );
    }

    /**
     * searches for state oxid by its ISO number or title
     *
     * @param string $sShopgateStateId
     *
     * @return null|string
     */
    public function getStateOxid($sShopgateStateId)
    {
        return marm_shopgate::dbGetOne("SELECT OXID FROM oxstates WHERE OXISOALPHA2 = ?", array($sShopgateStateId));
    }

    /**
     * @param string $shopgateGender
     *
     * @return null|string
     */
    public function getOxidGender($shopgateGender)
    {
        if ($shopgateGender == ShopgateCustomer::FEMALE) {
            return self::OXID_GENDER_FEMALE;
        }
        if ($shopgateGender == ShopgateCustomer::MALE) {
            return self::OXID_GENDER_MALE;
        }

        return null;
    }

    /**
     * @param string $stateKey
     *
     * @return string
     */
    public function getOxStateByIso($stateKey)
    {
        if (!is_array($this->states)) {
            $this->states = array(
                'DE-BW' => 'Baden-Württemberg',
                'DE-BY' => 'Bayern',
                'DE-BE' => 'Berlin',
                'DE-BB' => 'Brandenburg',
                'DE-HB' => 'Bremen',
                'DE-HH' => 'Hamburg',
                'DE-HE' => 'Hessen',
                'DE-MV' => 'Mecklenburg-Vorpommern',
                'DE-NI' => 'Niedersachsen',
                'DE-NW' => 'Nordrhein-Westfalen',
                'DE-RP' => 'Rheinland-Pfalz',
                'DE-SL' => 'Saarland',
                'DE-SN' => 'Sachsen',
                'DE-ST' => 'Sachsen-Anhalt',
                'DE-SH' => 'Schleswig-Holstein',
                'DE-TH' => 'Thüringen',
            );
        }

        return isset($this->states[$stateKey])
            ? $this->states[$stateKey]
            : '';
    }

    /**
     * @return bool
     */
    public function isGuestAccountCreated()
    {
        return $this->guestAccountCreated;
    }
}
