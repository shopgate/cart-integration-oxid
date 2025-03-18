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

namespace Shopgate\Oxid\Controller\Admin;

use Shopgate\Oxid\Core\Module;
use OxidEsales\Eshop\Application\Model\DeliverySet;
use OxidEsales\Eshop\Core\Field;

/**
 * Admin controller for Shopgate config tab
 */
class Shipping extends Shipping_parent
{
    /**
     * shopgate configuration template
     *
     * @var string
     */
    protected $_sThisTemplate = 'shopgate_shipping.tpl';

    /**
     * stores array for shopgate config, with information how to display it
     *
     * @var array
     */
    protected $_aShopgateConfig = null;

    protected $isError = false;

    protected $errorMessage = "";

    public function render()
    {
        $return = parent::render();

        $soxId = $this->_aViewData['oxid'] = $this->getEditObjectId();
        if ($soxId != "-1" && isset($soxId)) {
            // load object
            /** @var DeliverySet $deliverySet */
            $deliverySet = oxNew(DeliverySet::class);
            $deliverySet->loadInLang($this->_iEditLang, $soxId);

            $oOtherLang = $deliverySet->getAvailableInLangs();

            if (!isset($oOtherLang[$this->_iEditLang])) {
                // echo "language entry doesn't exist! using: ".key($oOtherLang);
                $deliverySet->loadInLang(key($oOtherLang), $soxId);
            }

            $this->_aViewData['edit'] = $deliverySet;
        }

        $this->_aViewData['delivery_services'] = $this->getDeliveryServiceList();

        return $return;
    }

    public function setDeliveryService()
    {
        /** @var DeliverySet $oDel */
        $oDel = oxNew(DeliverySet::class);

        // Works with oxid 4.3
        $fields = $oDel->getSelectFields();
        $fields = preg_replace("/`/", "", $fields);
        $fields = explode(", ", $fields);

        if (!in_array("{$oDel->getViewName()}.shopgate_service_id", $fields)) {
            $this->isError = true;
            $this->errorMessage = "Cannot save - The field 'shopgate_service_id' is missing";

            return;
        }

        $sOxid = Module::getRequestParameter('oxid');
        $sShopgateShippingServiceId = Module::getRequestParameter('shopgate_shipping_service_id');

        $oDel->load($sOxid);

        $oDel->oxdeliveryset__shopgate_service_id = new Field($sShopgateShippingServiceId, Field::T_RAW);
        $oDel->save();
    }

    public function getDeliveryServiceList()
    {
        return array(
            'DHL',
            'DHLEXPRESS',
            'DP',
            'DPD',
            'FEDEX',
            'GLS',
            'HLG',
            'TNT',
            'TOF',
            'UPS',
            'USPS',
            'OTHER',
        );
    }

    public function getIsError()
    {
        return $this->isError;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
