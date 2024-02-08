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
 * Class ShopgateBasketHelper
 */
class ShopgateBasketHelper extends ShopgateObject
{
    /** @var bool */
    private $errorOnInvalidCoupon;

    /** @var ShopgateUserHelper */
    private $userHelper;

    /** @var ShopgateVoucherHelper */
    private $voucherHelper;

    /** @var ShopgateShippingHelper */
    private $shippingHelper;

    /** @var Exception[] */
    private $cartItemExceptions = array();

    /**
     * @param ShopgateUserHelper     $shopgateUserHelper
     * @param ShopgateVoucherHelper  $shopgateVoucherHelper
     * @param ShopgateShippingHelper $shopgateShippingHelper
     */
    public function __construct(
        ShopgateUserHelper $shopgateUserHelper,
        ShopgateVoucherHelper $shopgateVoucherHelper,
        ShopgateShippingHelper $shopgateShippingHelper
    ) {
        $this->userHelper           = $shopgateUserHelper;
        $this->voucherHelper        = $shopgateVoucherHelper;
        $this->shippingHelper       = $shopgateShippingHelper;
        $this->errorOnInvalidCoupon = true;
    }

    /**
     * @param bool $errorOnInvalidCoupon
     */
    public function setErrorOnInvalidCoupon($errorOnInvalidCoupon)
    {
        $this->errorOnInvalidCoupon = $errorOnInvalidCoupon;
    }

    /**
     * @return Exception[]
     */
    public function getCartItemExceptions()
    {
        return $this->cartItemExceptions;
    }

    /**
     * @param ShopgateCartBase $shopgateOrder (either ShopgateCart or ShopgateOrder)
     * @param bool             $withShippingAndPayment
     * @param bool             $stockCheckMode
     *
     * @return shopgate_oxbasket
     *
     * @throws ShopgateLibraryException
     */
    public function buildOxBasket(
        ShopgateCartBase $shopgateOrder,
        $withShippingAndPayment = true,
        $stockCheckMode = false
    ) {
        /** @var shopgate_oxbasket $oxBasket */
        $oxBasket = marm_shopgate::getOxSession()->getBasket();
        $oxBasket->setStockCheckMode($stockCheckMode);

        $this->loadOrderBasket($oxBasket, $shopgateOrder);
        $this->loadOrderBasketContacts($oxBasket, $shopgateOrder);
        $this->loadOrderBasketArticles($oxBasket, $shopgateOrder);
        $this->addVouchersToBasket($oxBasket, $shopgateOrder);

        // Note: $oxBasket->calculateBasket() must have been called at least once at this point (it's called by addVouchersToBasket()),
        // otherwise the "most used VAT percent" (which are used for the shipping/payment costs) wouldn't be calculated correctly.

        if ($withShippingAndPayment) {
            $this->loadOrderBasketShipping($oxBasket, $shopgateOrder);
            $this->loadOrderBasketPayment($oxBasket, $shopgateOrder);
        }

        $this->loadOrderBasketTotal($oxBasket, $shopgateOrder);

        // As soon as the last calculateBasket() call happened we need to deactivate the deliverySet
        $this->shippingHelper->deactivateDeliverySet();

        // Note: $oxBasket->calculateBasket() must NOT be called after this point. It would override Shopgate's custom shipping/payment costs.

        return $oxBasket;
    }

    /**
     * set the basic configuration to the basket
     *
     * @param shopgate_oxbasket $oxBasket
     * @param ShopgateCartBase  $shopgateOrder
     *
     * @throws ShopgateLibraryException
     */
    protected function loadOrderBasket(shopgate_oxbasket &$oxBasket, ShopgateCartBase $shopgateOrder)
    {
        $oxBasket->setBasketCurrency(marm_shopgate::getOxConfig()->getCurrencyObject($shopgateOrder->getCurrency()));

        // Do not allow to save basket! !!IMPORTANT!!
        $oxBasket->getConfig()->setConfigParam('blPerfNoBasketSaving', true);
        $oxBasket->getConfig()->setConfigParam('blPsBasketReservationEnabled', false);

        if ($shopgateOrder instanceof ShopgateOrder) {
            $oxBasket->setDiscountCalcMode(false);
            $oxBasket->setSkipVouchersChecking(true);
        }

        // Set mobile shipping for the basket. Change to real shipping later after order is calculated because of wrong calculcated order totals otherwise
        $oxBasket->setShipping(ShopgateShippingHelper::SHIPPING_SERVICE_ID_MOBILE_SHIPPING);

        $this->shippingHelper->activateDeliverySet(20);
        $this->shippingHelper->updateDeliveryEntry($shopgateOrder);

        // Set mobile payment for the basket. Change to real payment later after order is calculated
        $oxBasket->setPayment(ShopgatePaymentHelper::PAYMENT_ID_MOBILE_PAYMENT);

        /** @var oxPayment $oxPayment */
        $oxPayment = oxNew('oxPayment');
        if ($oxPayment->load(ShopgatePaymentHelper::PAYMENT_ID_MOBILE_PAYMENT)) {
            $this->log("> Set amount of shoppayment to 'mobile_payment'", ShopgateLogger::LOGTYPE_DEBUG);
            $oxPayment->oxpayments__oxaddsum     = new oxField($shopgateOrder->getAmountShopPayment(), oxField::T_RAW);
            $oxPayment->oxpayments__oxaddsumtype = new oxField("abs", oxField::T_RAW);
            $oxPayment->save();
        } else {
            throw new ShopgateLibraryException(
                ShopgateLibraryException::UNKNOWN_ERROR_CODE,
                "Paymentmethod 'mobile_payment' not found",
                true
            );
        }
    }

    /**
     * @param shopgate_oxbasket $oxBasket
     * @param ShopgateCartBase  $shopgateOrder
     */
    protected function loadOrderBasketShipping(shopgate_oxbasket &$oxBasket, ShopgateCartBase $shopgateOrder)
    {
        // #9174 Christian Frenzl calculateBasket() aufrufen, da ansonsten die Mwst. der VSK (OXDELVAT) nicht berechnet werden
        $oxBasket->calculateBasket(true);

        if ($shopgateOrder instanceof ShopgateOrder) {
            $serviceId = $this->shippingHelper->getShippingServiceId($shopgateOrder);
            $oxBasket->setShipping($serviceId);
        }

        /** @var oxPrice $oxPrice */
        $oxPrice = oxNew('oxPrice');
        $oxPrice->setBruttoPriceMode();

        // $fDelVATPercent = $oxBasket->getMostUsedVatPercent(); // NOTE: THIS WILL NOT WORK IN OXID < 4.5
        $fDelVATPercent = $oxBasket->getProductsPrice()->getMostUsedVatPercent();
        $oxPrice->setVat($fDelVATPercent);

        $oxPrice->setPrice($shopgateOrder->getAmountShipping());

        $oxBasket->setDeliveryPrice($oxPrice);
        $oxBasket->setCost('oxdelivery', $oxPrice);
    }

    /**
     * @param shopgate_oxbasket $oxBasket
     * @param ShopgateCartBase  $shopgateOrder
     */
    protected function loadOrderBasketPayment(shopgate_oxbasket &$oxBasket, ShopgateCartBase $shopgateOrder)
    {
        $oxBasket->setPayment(ShopgatePaymentHelper::findOxidPaymentId($shopgateOrder));

        $paymentCost = $shopgateOrder->getAmountShopgatePayment() + $shopgateOrder->getAmountShopPayment();
        if ($paymentCost > 0) {
            /** @var oxPrice $oxPrice */
            $oxPrice = oxNew('oxPrice');
            $oxPrice->setBruttoPriceMode();
            $oxPrice->setVat($oxBasket->getProductsPrice()->getMostUsedVatPercent());
            $oxPrice->setPrice($paymentCost);
            $oxBasket->setCost('oxpayment', $oxPrice);
        }
    }

    /**
     * @param shopgate_oxbasket $oxidBasket
     * @param ShopgateCartBase  $shopgateCart
     *
     * @post if $oxidBasket supports the setPrice method price will be overwritten
     */
    protected function loadOrderBasketTotal(shopgate_oxbasket $oxidBasket, ShopgateCartBase $shopgateCart)
    {
        if (method_exists($oxidBasket,"setPrice")) {
            /** @var oxPrice $oxPrice */
            $oxPrice = oxNew('oxPrice');
            $oxPrice->setBruttoPriceMode();
            $oxPrice->setVat(
                $oxidBasket->getProductsPrice()->getMostUsedVatPercent()
            );
            $oxPrice->setPrice($shopgateCart->getAmountComplete());
            $oxidBasket->setPrice($oxPrice);
        }
    }

    /**
     * set the user to the basket
     * if the user does not exist in the system, a new one will be created
     *
     * @param shopgate_oxbasket $oxBasket
     * @param ShopgateCartBase  $shopgateOrder
     */
    protected function loadOrderBasketContacts(shopgate_oxbasket &$oxBasket, ShopgateCartBase $shopgateOrder)
    {
        $oxUser = $this->userHelper->getOxidUserByEmail($shopgateOrder);

        // Delete old basket if exists! Required for OXID 4.3.x & 4.4.x
        if ($oxUser) {
            $oxUser->getBasket("savedbasket")->delete();
        }

        $oxBasket->setUser($oxUser);
    }

    /**
     * @param ShopgateOrderItem $shopgateOrderItem
     * @param shopgate_oxbasket $oxBasket
     *
     * @return oxBasketItem | null
     */
    private function createBasketItem(ShopgateOrderItem &$shopgateOrderItem, shopgate_oxbasket &$oxBasket)
    {
        if ($shopgateOrderItem->getUnitAmountWithTax() < 0) {
            return null;
        }

        $infos = $this->jsonDecode($shopgateOrderItem->getInternalOrderInfo(), true);
        if (!$infos) {
            $infos = array();
        }
        if (!isset($infos['article_oxid'])) {
            $infos['article_oxid'] = $shopgateOrderItem->getItemNumber();
            $shopgateOrderItem->setInternalOrderInfo($this->jsonEncode($infos));
        }

        $aSel = array();
        foreach ($shopgateOrderItem->getOptions() as $option) {
            $aSel[] = $option->getValueNumber();
        }

        if (!empty($infos['selected_option'])) {
            foreach ($infos['selected_option'] as $option) {
                $aSel[] = $option['value_number'];
            }
        }

        $aPersParam = array();
        foreach ($shopgateOrderItem->getInputs() as $input) {
            $aPersParam['details'] = $input->getUserInput();
            break;
        }

        $vpe = !empty($infos['vpe'])
            ? $infos['vpe']
            : 1;
        $qty = $shopgateOrderItem->getQuantity() * $vpe;

        // oxPrice is set in marm_shopgate_oxarticle::getBasketPrice
        return $oxBasket->addToBasket($infos['article_oxid'], $qty, $aSel, $aPersParam);
    }

    /**
     * add the artiles to the basket
     *
     * @param shopgate_oxbasket $oxBasket
     * @param ShopgateCartBase  $shopgateOrder
     */
    protected function loadOrderBasketArticles(shopgate_oxbasket &$oxBasket, ShopgateCartBase $shopgateOrder)
    {
        $oxBasket->setDiscountCalcMode(false);
        marm_shopgate::getOxConfig()->setConfigParam("blOverrideZeroABCPrices", true);

        foreach ($shopgateOrder->getItems() as $shopgateOrderItem) {
            try {
                $oxBasketItem = $this->createBasketItem($shopgateOrderItem, $oxBasket);
            } catch (Exception $e) {
                $this->cartItemExceptions[$shopgateOrderItem->getItemNumber()] = $e;
            }
            if (empty($oxBasketItem)) {
                continue;
            }

            $infos = $this->jsonDecode($shopgateOrderItem->getInternalOrderInfo(), true);

            // Set custom price from shopgate if price has changed in oxid
            // once the article is loaded the function oxBasket::calculateBasket() will use this cached article
            // FIX #7556 Need to enable lazy loading here for oxid 4.6!!
            if ($oxArticle = $oxBasketItem->getArticle(false, $infos["article_oxid"], true)) {
                $vpe = !empty($infos['vpe'])
                    ? $infos['vpe']
                    : 1;

                /** @var oxArticle $oxArticle */
                $price = $oxBasket->isCalculationModeNetto()
                    ? $shopgateOrderItem->getUnitAmount()
                    : $shopgateOrderItem->getUnitAmountWithTax();
                $price /= $vpe;

                // need for oxPrice in marm_shopgate_oxarticle::getBasketPrice
                $oxArticle->sg_order_item = $shopgateOrderItem;

                // NOTE: Do Not save this artilce!
                $oxArticle->oxarticles__oxprice  = new oxField($price, oxField::T_RAW);
                $oxArticle->oxarticles__oxpricea = new oxField(0, oxField::T_RAW);
                $oxArticle->oxarticles__oxpriceb = new oxField(0, oxField::T_RAW);
                $oxArticle->oxarticles__oxpricec = new oxField(0, oxField::T_RAW);

                // Set article selction price to 0 because it is include in UnitAmountWithTax from shopgate item
                foreach ($oxArticle->getSelectLists() as $aList) {
                    foreach ($aList as &$aValues) {
                        if (is_object($aValues)) {
                            $aValues->price  = 0;
                            $aValues->fprice = 0;
                        }
                    }
                }
            }
        }
    }

    /**
     * Add all vouchers in Shopgate cart to basket
     *
     * @throws ShopgateLibraryException
     *
     * @param shopgate_oxbasket $oxBasket
     * @param ShopgateCartBase  $cart
     *
     * @return oxBasket
     */
    protected function addVouchersToBasket(shopgate_oxbasket &$oxBasket, ShopgateCartBase $cart)
    {
        $coupons = array();
        foreach ($cart->getShopgateCoupons() as $coupon) {
            $coupons[-1 * $coupon->getOrderIndex()] = $coupon;
        }
        $i = 1;
        foreach ($cart->getExternalCoupons() as $coupon) {
            $orderIndex           = ($coupon->getOrderIndex() === null)
                ? $i++
                : $coupon->getOrderIndex();
            $coupons[$orderIndex] = $coupon;
        }
        ksort($coupons);

        $oxBasket->setSkipVouchersChecking(false);

        // Need Basket calculation in OXID 4.3 !!!
        $oxBasket->calculateBasket(true);

        foreach ($coupons as $coupon) {
            $this->addVoucherToBasket($oxBasket, $coupon);
        }

        $oxBasket->setSkipVouchersChecking(false);
        $oxBasket->calculateBasket(true);

        return $oxBasket;
    }

    /**
     * @param shopgate_oxbasket $oxBasket
     * @param ShopgateCoupon    $coupon
     *
     * @throws Exception
     * @throws ShopgateLibraryException
     */
    private function addVoucherToBasket(shopgate_oxbasket $oxBasket, ShopgateCoupon $coupon)
    {
        $countBefore = count($oxBasket->getVouchers());

        $infos = $this->jsonDecode($coupon->getInternalInfo(), true);
        if (!empty($infos['sVoucherId'])) {
            /** @var oxVoucher $oxVoucher */
            $oxVoucher = oxNew('oxVoucher');
            if (!$oxVoucher->load($infos['sVoucherId'])) {
                // This should never happen because check_cart returns the correct voucher oxid
                throw new ShopgateLibraryException(
                    ShopgateLibraryException::COUPON_NOT_VALID, "Cannot find coupon (id: {$infos['sVoucherId']})", true
                );
            }

            if (!($this->voucherHelper->isVoucherAlreadyUsed($oxVoucher))) {
                $oxBasket->setSkipVouchersChecking(true);
                $oxBasket->addVoucher($infos['sVoucherId']);
                $oxBasket->setSkipVouchersChecking(false);

                $countAfter = count($oxBasket->getVouchers());
                if ($countAfter <= $countBefore && $this->errorOnInvalidCoupon) {
                    throw new ShopgateLibraryException(
                        ShopgateLibraryException::COUPON_NOT_VALID,
                        "Coupon was not added to cart (id: {$infos['sVoucherId']})"
                    );
                }

                // re-mark the voucher as reserved, so that the 3 hours of reservation start anew
                $oxVoucher->markAsReserved();

                return;
            }
        }

        $couponCode = $coupon->getCode();
        try {
            if ($coupon instanceof ShopgateShopgateCoupon) {
                /** @var oxVoucherSerie $oxVoucherSeries */
                $oxVoucherSeries = oxNew('oxVoucherSerie');

                if (!$oxVoucherSeries->load(ShopgateVoucherHelper::VOUCHER_OXID)) {
                    $oxVoucherSeries = $this->voucherHelper->createVoucherSeriesForShopgate();
                } else {
                    $oxVoucherSeries->oxvoucherseries__oxallowsameseries  = new oxField(1, oxField::T_RAW);
                    $oxVoucherSeries->oxvoucherseries__oxallowotherseries = new oxField(1, oxField::T_RAW);
                    $oxVoucherSeries->oxvoucherseries__oxallowuseanother  = new oxField(1, oxField::T_RAW);
                    $oxVoucherSeries->oxvoucherseries__oxminimumvalue     = new oxField(0, oxField::T_RAW);
                    $oxVoucherSeries->oxvoucherseries__oxcalculateonce    = new oxField(0, oxField::T_RAW);
                    $oxVoucherSeries->save();
                }

                /** @var oxVoucher $oxVoucher */
                $oxVoucher = oxNew('oxVoucher');
                if ($oxBasket->getUser()) {
                    $oxVoucher->oxvouchers__oxuserid = new oxField($oxBasket->getUser()->getId(), oxField::T_RAW);
                }

                //$oxVoucher->oxvouchers__oxreserved = new oxField(0, oxField::T_RAW);
                $discount = $oxBasket->isCalculationModeNetto() ? $coupon->getAmountNet() : $coupon->getAmountGross();
                $oxVoucher->oxvouchers__oxvouchernr           = new oxField($couponCode, oxField::T_RAW);
                $oxVoucher->oxvouchers__oxvoucherserieid      = new oxField($oxVoucherSeries->getId(), oxField::T_RAW);
                $oxVoucher->oxvouchers__oxdiscount            = new oxField($discount, oxField::T_RAW);
                $oxVoucherSeries->oxvoucherseries__oxenddate  = new oxField(date('Y-m-d H:i:s', time() + 60));
                $oxVoucherSeries->oxvoucherseries__oxdiscount = new oxField($discount, oxField::T_RAW);
                $oxVoucherSeries->save();

                if (_SHOPGATE_ACTION == ShopgatePluginOxid::ACTION_ADD_ORDER) {
                    $oxVoucher->save();
                    $oxVoucherId = $oxVoucher->getId();
                }
            }

            // Note: oxBasket::addVoucher($voucherId) requires voucher_oxid
            // if skip voucher check is true BUT needs coupon code if skip is false!
            if (!empty($oxVoucher) && !empty($oxVoucherId)) {
                $oxBasket->setSkipVouchersChecking(true);
                $oxVoucher->markAsReserved();
                $oxBasket->addVoucher($oxVoucherId);
                $oxBasket->setSkipVouchersChecking(false);
            } else {
                $oxBasket->addVoucher($couponCode);
            }

            $countAfter = count($oxBasket->getVouchers());
            if ($countAfter <= $countBefore) {
                // If count hasn't increased, coupon was not added and is invalid
                throw new ShopgateLibraryException(
                    ShopgateLibraryException::COUPON_NOT_VALID, "Coupon was not added to cart (code: $couponCode)", true
                );
            }
        } catch (Exception $e) {
            $voucherErrors = $this->getVoucherErrors();
            if (!empty($voucherErrors)) {
                $this->log("voucherErrors: " . print_r($voucherErrors, true));
            }
            if ($this->errorOnInvalidCoupon) {
                if ($e instanceof oxVoucherException && $e->getMessage() == 'EXCEPTION_VOUCHER_NOVOUCHER') {
                    throw new ShopgateLibraryException(ShopgateLibraryException::COUPON_CODE_NOT_VALID);
                }
                throw $e;
            }
        }
    }

    /**
     * @return string[]
     */
    public function getVoucherErrors()
    {
        $result = array();
        $errors = marm_shopgate::getSessionVar('Errors');
        if (!empty($errors)) {
            foreach ($errors as $_errors) {
                foreach ($_errors as $error) {
                    /** @var oxExceptionToDisplay $error */
                    $error = unserialize($error);

                    $voucher          = strtolower($error->getValue('voucherNr'));
                    $result[$voucher] = $error->getOxMessage();
                }
            }
        }

        return $result;
    }

    /**
     * @param string $msg
     * @param string $type
     *
     * @return null
     */
    public function log($msg, $type = ShopgateLogger::LOGTYPE_ERROR)
    {
        if (!empty($_REQUEST['trace_id'])) {
            $msg = "[{$_REQUEST['trace_id']}] $msg";
        }
        parent::log($msg, $type);

        return;
    }
}
