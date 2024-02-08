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
 * Class ShopgateShippingHelper
 */
class ShopgateShippingHelper extends ShopgateObject
{
    const SHIPPING_SERVICE_ID_MOBILE_SHIPPING = 'mobile_shipping';

    /**
     * @param ShopgateCartBase $cartOrOrder
     *
     * @return string
     */
    public function getShippingServiceId(ShopgateCartBase $cartOrOrder)
    {
        if ($cartOrOrder->getShippingType() == 'PLUGINAPI') {
            return $cartOrOrder->getShippingInfos()->getName();
        }

        $group = $cartOrOrder->getShippingGroup();

        /** @var oxCountry $oxCountry */
        $oxCountry = oxNew('oxCountry');

        /** @var oxDeliverySet $oxDeliverySet */
        $oxDeliverySet = oxNew('oxDeliverySet');

        // get shipping set from database and optional iso country code
        $sql = "
				SELECT `ds`.`OXID`, `c`.`OXISOALPHA2`
				FROM `{$oxDeliverySet->getViewName()}` ds
				LEFT JOIN `oxobject2delivery` o2d ON ( o2d.oxdeliveryid = ds.oxid )
				LEFT JOIN `{$oxCountry->getViewName()}` c ON ( o2d.oxobjectid = c.oxid )
				WHERE `ds`.`shopgate_service_id` = ?
				  AND ( `c`.`OXISOALPHA2` = ? OR o2d.oxid IS NULL )";

        $country = null;
        if ($cartOrOrder->getDeliveryAddress() && $cartOrOrder->getDeliveryAddress()->getCountry()) {
            $country = $cartOrOrder->getDeliveryAddress()->getCountry();
        } elseif ($cartOrOrder->getInvoiceAddress() && $cartOrOrder->getInvoiceAddress()->getCountry()) {
            $country = $cartOrOrder->getInvoiceAddress()->getCountry();
        }
        $aSetList = marm_shopgate::dbGetAll($sql, array($group, $country));

        if (!empty($aSetList)) {
            foreach ($aSetList as $aSetRow) {
                if ($aSetRow['OXISOALPHA2']) {
                    // if iso code is available => use this and break
                    return $aSetRow['OXID'];
                }
            }
        }

        return self::SHIPPING_SERVICE_ID_MOBILE_SHIPPING;
    }

    /**
     * Activates our already existing DeliverySet (which is created by installing our module and by default
     * deactivated) for a few seconds so it will be recognized by Oxid when we want to calculateBasket()
     *
     * @param int $seconds
     */
    public function activateDeliverySet($seconds)
    {
        /** @var OxDeliverySet $oxShipping */
        $oxDeliverySet = oxNew('oxDeliverySet');
        if ($oxDeliverySet->load(ShopgateShippingHelper::SHIPPING_SERVICE_ID_MOBILE_SHIPPING)) {
            $oxDeliverySet->oxdeliveryset__oxactivefrom = new oxField(
                date("Y-m-d H:i:s", marm_shopgate::getOxUtilsDate()->getTime() - 1), oxField::T_RAW
            );
            $oxDeliverySet->oxdeliveryset__oxactiveto   =
                new oxField(date("Y-m-d H:i:s", marm_shopgate::getOxUtilsDate()->getTime() + $seconds), oxField::T_RAW);
            $oxDeliverySet->save();
        }
    }

    /**
     *Deactivates our already existing DeliverySet
     */
    public function deactivateDeliverySet()
    {
        /** @var OxDeliverySet $oxShipping */
        $oxDeliverySet = oxNew('oxDeliverySet');
        if ($oxDeliverySet->load(ShopgateShippingHelper::SHIPPING_SERVICE_ID_MOBILE_SHIPPING)) {
            $oxDeliverySet->oxdeliveryset__oxactivefrom = new oxField("0000-00-00 00:00:00", oxField::T_RAW);
            $oxDeliverySet->oxdeliveryset__oxactiveto   = new oxField("0000-00-00 00:00:00", oxField::T_RAW);
            $oxDeliverySet->save();
        }
    }

    /**
     * @param ShopgateCartBase $shopgateOrder
     *
     * @return oxDelivery
     */
    public function updateDeliveryEntry(ShopgateCartBase $shopgateOrder)
    {
        /** @var OxDelivery $oxDelivery */
        $oxDelivery = oxNew('oxDelivery');

        if (!$oxDelivery->load(ShopgateShippingHelper::SHIPPING_SERVICE_ID_MOBILE_SHIPPING)) {
            $oxDelivery = $this->createDeliveryEntry($shopgateOrder);
            $this->createMappingDeliverySetToDelivery($oxDelivery);
        } else {
            $this->updateDeliveryFields($shopgateOrder, $oxDelivery);
            $oxDelivery->save();
        }

        return $oxDelivery;
    }

    /**
     * @param ShopgateCartBase $shopgateOrder
     *
     * @return oxDelivery
     */
    public function createDeliveryEntry(ShopgateCartBase $shopgateOrder)
    {
        /** @var oxDelivery $oxDelivery */
        $oxDelivery                           = oxNew('oxDelivery');
        $oxDelivery->oxdelivery__oxid         = new oxField(
            ShopgateShippingHelper::SHIPPING_SERVICE_ID_MOBILE_SHIPPING,
            oxField::T_RAW
        );
        $oxDelivery->oxdelivery__oxactive     = new oxField(1, oxField::T_RAW);
        $oxDelivery->oxdelivery__oxtitle      = new oxField('Shopgate Mobile Shipping', oxField::T_RAW);
        $oxDelivery->oxdelivery__oxaddsumtype = new oxField('abs', oxField::T_RAW);
        $oxDelivery->oxdelivery__oxdeltype    = new oxField('p', oxField::T_RAW);
        $oxDelivery->oxdelivery__oxsort       = new oxField(99999, oxField::T_RAW);
        $oxDelivery->oxdelivery__oxfinalize   = new oxField(1, oxField::T_RAW);

        $this->updateDeliveryFields($shopgateOrder, $oxDelivery);

        $oxDelivery->save();

        marm_shopgate::dbExecute(
            "UPDATE oxdelivery SET oxid = '" . self::SHIPPING_SERVICE_ID_MOBILE_SHIPPING . "' WHERE oxid = '$oxDelivery->oxdelivery__oxid'"
        );

        $oxDelivery->oxdelivery__oxid = self::SHIPPING_SERVICE_ID_MOBILE_SHIPPING;

        return $oxDelivery;
    }

    /**
     * Manipulates the parameter $oxDelivery by setting the oxparam, oxparamend and oxaddsum
     *
     * @param ShopgateCartBase $shopgateOrder
     * @param OxDelivery       $oxDelivery
     */
    public function updateDeliveryFields(ShopgateCartBase $shopgateOrder, $oxDelivery)
    {
        $oxDelivery->oxdelivery__oxparam    = new oxField(
            floor($shopgateOrder->getAmountItems()),
            oxField::T_RAW
        );
        $oxDelivery->oxdelivery__oxparamend = new oxField(
            ceil($shopgateOrder->getAmountItems()),
            oxField::T_RAW
        );
        $oxDelivery->oxdelivery__oxaddsum   = new oxField($shopgateOrder->getAmountShipping(), oxField::T_RAW);
    }

    /**
     * @param oxDelivery $oxDelivery
     */
    public function createMappingDeliverySetToDelivery($oxDelivery)
    {
        $oDel2delset = oxNew('oxbase');
        $oDel2delset->init('oxdel2delset');
        $oDel2delset->oxdel2delset__oxdelid    = new oxField($oxDelivery->oxdelivery__oxid);
        $oDel2delset->oxdel2delset__oxdelsetid = new oxField(
            ShopgateShippingHelper::SHIPPING_SERVICE_ID_MOBILE_SHIPPING
        );
        $oDel2delset->save();
    }
}
