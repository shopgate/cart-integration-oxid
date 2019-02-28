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

class ShopgateOrderExportHelper extends ShopgateObject
{
    /** @var ShopgateConfigOxid */
    private $config;

    /** @var string */
    private $uniqueArticleIdField;

    /** @var string */
    private $lang;

    /** @var oxOrder */
    private $oxOrder;

    public function __construct(ShopgateConfigOxid $config)
    {
        $this->config               = $config;
        $this->uniqueArticleIdField = $this->config->getArticleIdentifier();
    }

    /**
     * @param string $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * @param oxOrder $oxOrder
     *
     * @return ShopgateExternalOrder
     */
    public function buildExternalOrder(oxOrder $oxOrder)
    {
        $this->oxOrder = $oxOrder;
        $order         = new ShopgateExternalOrder();
        $order->setOrderNumber($this->getOrderNumber());
        $order->setExternalOrderNumber($this->getExternalOrderNumber());
        $order->setExternalOrderId($this->getExternalOrderId());
        $order->setCreatedTime($this->getCreatedTime());
        $order->setMail($this->getMail());
        $order->setPhone($this->getPhone());
        $order->setMobile($this->getMobile());
        $order->setCustomFields($this->getCetCustomFields());
        $order->setInvoiceAddress($this->getInvoiceAddress());
        $order->setDeliveryAddress($this->getDeliveryAddress());
        $order->setCurrency($this->getCurrency());
        $order->setAmountComplete($this->getAmountComplete());
        $order->setIsPaid($this->getIsPaid());
        $order->setPaymentMethod($this->getPaymentMethod());
        $order->setPaymentTime($this->getPaymentTime());
        $order->setPaymentTransactionNumber($this->getPaymentTransactionNumber());
        $order->setIsShippingCompleted($this->getIsShippingCompleted());
        $order->setShippingCompletedTime($this->getShippingCompletedTime());
        $order->setDeliveryNotes($this->getDeliveryNotes());
        $order->setOrderTaxes($this->getOrderTaxes());
        $order->setExtraCosts($this->getExtraCosts());
        $order->setExternalCoupons($this->getExternalCoupons());
        $order->setItems($this->getItems());

        return $order;
    }

    ###################################################################################################################
    ## Getters
    ###################################################################################################################

    /**
     * @return string
     */
    private function getOrderNumber()
    {
        /** @var oxOrderShopgate $oxOrderShopgate */
        $oxOrderShopgate = oxNew('oxOrderShopgate');
        if ($oxOrderShopgate->load($this->oxOrder->oxorder__oxid->value, 'oxorderid')) {
            return $oxOrderShopgate->oxordershopgate__order_number->value;
        }

        return '';
    }

    /**
     * @return string
     */
    private function getExternalOrderNumber()
    {
        return $this->oxOrder->oxorder__oxordernr->value;
    }

    /**
     * @return string
     */
    private function getExternalOrderId()
    {
        return $this->oxOrder->oxorder__oxid->value;
    }

    /**
     * @return string timestamp
     */
    private function getCreatedTime()
    {
        return $this->formatTime($this->oxOrder->oxorder__oxorderdate->value);
    }

    /**
     * @return string
     */
    private function getMail()
    {
        return $this->oxOrder->oxorder__oxbillemail->value;
    }

    /**
     * @return string
     */
    private function getPhone()
    {
        return $this->oxOrder->oxorder__oxbillfon->value;
    }

    /**
     * @return string
     */
    private function getMobile()
    {
        return '';
    }

    /**
     * @return ShopgateOrderCustomField[]
     */
    private function getCetCustomFields()
    {
        $result = array();
        //$field = new ShopgateOrderCustomField();
        //$result[] = $field;
        return $result;
    }

    /**
     * @return ShopgateAddress
     */
    private function getInvoiceAddress()
    {
        $country = marm_shopgate::getCountryCodeByOxid($this->oxOrder->oxorder__oxbillcountryid->value);
        $state   = marm_shopgate::getStateCodeByOxid($this->oxOrder->oxorder__oxbillstateid->value);

        $result = new ShopgateAddress();
        $result->setIsInvoiceAddress(true);
        $result->setFirstName($this->oxOrder->oxorder__oxbillfname->value);
        $result->setLastName($this->oxOrder->oxorder__oxbilllname->value);
        $result->setGender(marm_shopgate::getGenderByOxidSalutation($this->oxOrder->oxorder__oxbillsal->value));
        $result->setCompany($this->oxOrder->oxorder__oxbillcompany->value);
        $result->setStreet1(
            $this->oxOrder->oxorder__oxbillstreet->value . ' ' . $this->oxOrder->oxorder__oxbillstreetnr->value
        );
        $result->setStreet2($this->oxOrder->oxorder__oxbilladdinfo->value);
        $result->setZipcode($this->oxOrder->oxorder__oxbillzip->value);
        $result->setCity($this->oxOrder->oxorder__oxbillcity->value);
        $result->setCountry($country);
        $result->setState(
            (!empty($country) && !empty($state))
                ? "$country-$state"
                : ''
        );
        $result->setPhone($this->oxOrder->oxorder__oxbillfon->value);
        $result->setMail($this->oxOrder->oxorder__oxbillemail->value);

        return $result;
    }

    /**
     * @return ShopgateAddress
     */
    private function getDeliveryAddress()
    {
        $country = marm_shopgate::getCountryCodeByOxid($this->oxOrder->oxorder__oxdelcountryid->value);
        $state   = marm_shopgate::getStateCodeByOxid($this->oxOrder->oxorder__oxdelstateid->value);

        $result = new ShopgateAddress();
        $result->setIsDeliveryAddress(true);
        $result->setFirstName($this->oxOrder->oxorder__oxdelfname->value);
        $result->setLastName($this->oxOrder->oxorder__oxdellname->value);
        $result->setGender(marm_shopgate::getGenderByOxidSalutation($this->oxOrder->oxorder__oxbillsal->value));
        $result->setCompany($this->oxOrder->oxorder__oxdelcompany->value);
        $result->setStreet1($this->oxOrder->oxorder__oxdelstreet->value);
        $result->setStreet2($this->oxOrder->oxorder__oxdeladdinfo->value);
        $result->setZipcode($this->oxOrder->oxorder__oxdelzip->value);
        $result->setCity($this->oxOrder->oxorder__oxdelcity->value);
        $result->setCountry($country);
        $result->setState(
            (!empty($country) && !empty($state))
                ? "$country-$state"
                : ''
        );
        $result->setPhone($this->oxOrder->oxorder__oxdelfon->value);
        $result->setMail($this->oxOrder->oxorder__oxbillemail->value);

        return $result;
    }

    /**
     * @return string
     */
    private function getCurrency()
    {
        return $this->oxOrder->oxorder__oxcurrency->value;
    }

    /**
     * @return string
     */
    private function getAmountComplete()
    {
        return $this->oxOrder->oxorder__oxtotalordersum->value;
    }

    /**
     * @return int (0|1)
     */
    private function getIsPaid()
    {
        return empty($this->oxOrder->oxorder__oxpaid->value)
            ? 0
            : 1;
    }

    /**
     * @return string
     */
    private function getPaymentMethod()
    {
        $oxpaymentid = $this->oxOrder->oxorder__oxpaymenttype->value;
        /** @var oxPayment $oxPayment */
        $oxPayment = oxNew('oxPayment');
        if (!empty($oxpaymentid) && $oxPayment->load($oxpaymentid)) {
            if (!empty($oxPayment->oxpayments__shopgate_payment_method->value)) {
                return $oxPayment->oxpayments__shopgate_payment_method->value;
            }
        }

        return '';
    }

    /**
     * @return string
     */
    private function getPaymentTime()
    {
        return $this->formatTime($this->oxOrder->oxorder__oxpaid->value);
    }

    /**
     * @return string
     */
    private function getPaymentTransactionNumber()
    {
        # TODO implement
        return '';
    }

    /**
     * @return int (0|1)
     */
    private function getIsShippingCompleted()
    {
        return empty($this->oxOrder->oxorder__oxsenddate->value)
            ? 0
            : 1;
    }

    /**
     * @return string
     */
    private function getShippingCompletedTime()
    {
        return $this->formatTime($this->oxOrder->oxorder__oxsenddate->value);
    }

    /**
     * @return ShopgateDeliveryNote[]
     */
    private function getDeliveryNotes()
    {
        $result = array();
        if (!empty($this->oxOrder->oxorder__oxsenddate->value)) {
            $deliveryNote = new ShopgateDeliveryNote();
            $deliveryNote->setShippingTime($this->formatTime($this->oxOrder->oxorder__oxsenddate->value));
            $deliveryNote->setTrackingNumber($this->oxOrder->oxorder__oxtrackcode->value);
            $deliveryNote->setShippingServiceId($this->getShippingServiceId($this->oxOrder->oxorder__oxdeltype->value));
            $result[] = $deliveryNote;
        }

        return $result;
    }

    /**
     * @param string $oxdeltype
     *
     * @return string
     */
    private function getShippingServiceId($oxdeltype)
    {
        /** @var oxDeliverySet $oxDeliverySet */
        $oxDeliverySet = oxNew('oxDeliverySet');
        if (!empty($oxdeltype) && $oxDeliverySet->load($oxdeltype)) {
            if (!empty($oxDeliverySet->oxdeliveryset__shopgate_service_id->value)) {
                return $oxDeliverySet->oxdeliveryset__shopgate_service_id->value;
            }
        }

        return '';
    }

    /**
     * @return ShopgateExternalOrderTax[]
     */
    private function getOrderTaxes()
    {
        $result = array();
        for ($i = 1; $i <= 2; $i++) {
            if (!empty($this->oxOrder->{"oxorder__oxartvat{$i}"}->value) && !empty($this->oxOrder->{"oxorder__oxartvatprice{$i}"}->value)) {
                $tax = new ShopgateExternalOrderTax();
                $tax->setTaxPercent($this->oxOrder->{"oxorder__oxartvat{$i}"}->value);
                $tax->setAmount($this->oxOrder->{"oxorder__oxartvatprice{$i}"}->value);
                $tax->setLabel($this->oxOrder->{"oxorder__oxartvat{$i}"}->value . '%');
                $result[] = $tax;
            }
        }

        return $result;
    }

    /**
     * @return ShopgateExternalOrderExtraCost[]
     */
    private function getExtraCosts()
    {
        $result   = array();
        $result[] = $this->buildExtracost(
            ShopgateExternalOrderExtraCost::TYPE_SHIPPING,
            $this->oxOrder->oxorder__oxdelvat->value,
            $this->oxOrder->oxorder__oxdelcost->value
        );
        $result[] = $this->buildExtracost(
            ShopgateExternalOrderExtraCost::TYPE_PAYMENT,
            $this->oxOrder->oxorder__oxpayvat->value,
            $this->oxOrder->oxorder__oxpaycost->value
        );
        $result[] = $this->buildExtracost(
            ShopgateExternalOrderExtraCost::TYPE_MISC,
            $this->oxOrder->oxorder__oxwrapvat->value,
            $this->oxOrder->oxorder__oxwrapcost->value,
            $this->lang == 'de'
                ? 'Verpackungskosten'
                : 'Wrapping costs'
        );

        return $result;
    }

    /**
     * @param string $type
     * @param float  $taxPercent
     * @param float  $amount
     * @param string $label
     *
     * @return ShopgateExternalOrderExtraCost
     */
    private function buildExtracost($type, $taxPercent, $amount, $label = '')
    {
        $cost = new ShopgateExternalOrderExtraCost();
        $cost->setType($type);
        $cost->setTaxPercent($taxPercent);
        $cost->setAmount($amount);
        $cost->setLabel($label);

        return $cost;
    }

    /**
     * @return ShopgateExternalCoupon[]
     */
    private function getExternalCoupons()
    {
        $orderId = $this->oxOrder->oxorder__oxid->value;
        $ids     = marm_shopgate::dbGetAll("SELECT oxid FROM oxvouchers WHERE oxorderid = '$orderId'");

        $result = array();
        foreach ($ids as $id) {
            $id = array_shift($id);
            /** @var oxVoucher $oxVoucher */
            $oxVoucher = oxNew('oxVoucher');
            if ($oxVoucher->load($id)) {
                $result[] = $this->getCoupon($oxVoucher, $this->oxOrder->oxorder__oxcurrency->value);
            }
        }

        return $result;
    }

    /**
     * @param oxVoucher $oxVoucher
     * @param string    $currency
     *
     * @return ShopgateExternalCoupon
     */
    private function getCoupon(oxVoucher $oxVoucher, $currency)
    {
        $oxVoucherSeries = $oxVoucher->getSerie();
        $coupon          = new ShopgateExternalCoupon();
        $coupon->setAmountGross($oxVoucher->oxvouchers__oxdiscount->value);
        $coupon->setCode($oxVoucher->oxvouchers__oxvouchernr->value);
        $coupon->setCurrency($currency);
        $coupon->setDescription($oxVoucherSeries->oxvoucherseries__oxseriedescription->value);
        $coupon->setIsFreeShipping(false);
        $coupon->setName($oxVoucherSeries->oxvoucherseries__oxserienr->value);

        return $coupon;
    }

    /**
     * @return ShopgateExternalOrderItem[]
     */
    private function getItems()
    {
        $orderId = $this->oxOrder->oxorder__oxid->value;
        $ids     = marm_shopgate::dbGetAll("SELECT oxid FROM oxorderarticles WHERE oxorderid = '$orderId'");

        $result = array();
        foreach ($ids as $id) {
            $id = array_shift($id);
            /** @var oxOrderArticle $oxOrderArticle */
            $oxOrderArticle = oxNew('oxOrderArticle');
            $oxOrderArticle->load($id);
            $result[] = $this->getItem($oxOrderArticle, $this->oxOrder->oxorder__oxcurrency->value);
        }

        return $result;
    }

    /**
     * @param oxOrderArticle $oxOrderArticle
     * @param string         $currency
     *
     * @return ShopgateExternalOrderItem
     */
    private function getItem(oxOrderArticle $oxOrderArticle, $currency)
    {
        $item             = new ShopgateExternalOrderItem();
        $oxArticle        = $oxOrderArticle->getArticle();
        $itemNumberPrefix = '';

        if (!$oxArticle->getParentArticle()
            && !$oxArticle->sg_act_as_child && $this->config->isVariantParentBuyable()
        ) {
            $itemNumberPrefix = 'parent';
        }

        if ($this->uniqueArticleIdField == 'oxid') {
            $item->setItemNumber($itemNumberPrefix . $oxOrderArticle->oxorderarticles__oxartid->value);
        } else {
            $item->setItemNumber($itemNumberPrefix . $oxOrderArticle->oxorderarticles__oxartnum->value);
        }

        $item->setItemNumberPublic($oxOrderArticle->oxorderarticles__oxartnum->value);
        $item->setQuantity($oxOrderArticle->oxorderarticles__oxamount->value);
        $item->setName($oxOrderArticle->oxorderarticles__oxtitle->value);
        $item->setUnitAmount($oxOrderArticle->oxorderarticles__oxnetprice->value);
        $item->setUnitAmountWithTax($oxOrderArticle->oxorderarticles__oxbrutprice->value);
        $item->setTaxPercent($oxOrderArticle->oxorderarticles__oxvat->value);
        $item->setCurrency($currency);
        $item->setDescription($oxOrderArticle->oxorderarticles__oxshortdesc->value);

        return $item;
    }

    ###################################################################################################################
    ## Helpers
    ###################################################################################################################

    private function formatTime($value)
    {
        if (strlen($value) < 4 || $value == '0000-00-00 00:00:00') {
            return '';
        }

        return date('Y-m-d\TH:i:s', strtotime($value));
    }
}
