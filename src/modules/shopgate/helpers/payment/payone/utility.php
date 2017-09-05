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
class ShopgatePaymentHelperPayoneUtility extends ShopgatePaymentHelper
{
    const PAYONE_TX_ACTION_APPOINTED = 'appointed';
    const PAYONE_TX_ACTION_PAID      = 'paid';

    /** @var int | null $refNumber */
    protected $refNumber;

    /** @var ShopgatePayonePaymentInfos $paymentInfos */
    protected $paymentInfos;

    /**
     * Loads PayOne payment info into the oXid order
     */
    public function loadOrderPaymentInfos()
    {
        parent::loadOrderPaymentInfos();

        $module = $this->getPayoneModule();
        if (!$module->isActive()) {
            return;
        }

        $this->paymentInfos = new ShopgatePayonePaymentInfos(
            $this->shopgateOrder->getPaymentInfos(),
            $this->getRefNumber()
        );

        $this->updateOrderObjectFields();
        $this->updatePayoneOrderTransactionId();
        $this->generateApiRequestLog();
    }

    /**
     * Sets the necessary order fields for PayOne mapping to work
     */
    protected function updateOrderObjectFields()
    {
        $this->oxOrder->oxorder__fcpotxid            = new oxField($this->paymentInfos->getTxId(), oxField::T_RAW);
        $this->oxOrder->oxorder__fcporefnr           = new oxField($this->getRefNumber(), oxField::T_RAW);
        $this->oxOrder->oxorder__fcpoauthmode        = new oxField(
            $this->paymentInfos->getRequestType(), oxField::T_RAW
        );
        $this->oxOrder->oxorder__fcpomode            = new oxField($this->paymentInfos->getMode(), oxField::T_RAW);
        $this->oxOrder->oxorder__fcpoordernotchecked = $this->isCreditCheckDeclined();
    }

    /**
     * Make it look like the Oxid system made a PayOne API call
     * to auth or pre-auth the transaction
     */
    protected function generateApiRequestLog()
    {
        $serializedRequestParameter = serialize($this->paymentInfos->getRequestParameter($this->shopgateOrder));
        $serializedResponse         = serialize($this->paymentInfos->getResponse());

        $insertData = array(
            'FCPO_REFNR'          => oxDb::getDb()->quote($this->getRefNumber()),
            'FCPO_REQUESTTYPE'    => oxDb::getDb()->quote($this->paymentInfos->getRequestType()),
            'FCPO_RESPONSESTATUS' => oxDb::getDb()->quote($this->paymentInfos->getResponseStatus()),
            'FCPO_REQUEST'        => oxDb::getDb()->quote($serializedRequestParameter),
            'FCPO_RESPONSE'       => oxDb::getDb()->quote($serializedResponse),
            'FCPO_PORTALID'       => oxDb::getDb()->quote($this->paymentInfos->getPortalId()),
            'FCPO_AID'            => oxDb::getDb()->quote($this->paymentInfos->getSubAccountId()),
        );
        $keys       = implode(',', array_keys($insertData));
        $values     = implode(',', array_values($insertData));
        $query      = "INSERT INTO fcporequestlog ({$keys}) VALUES ({$values})";
        oxDb::getDb()->Execute($query);
    }

    /**
     * @param int                        $referenceNumber
     * @param ShopgatePayonePaymentInfos $payonePaymentInfos
     * @param ShopgateOrder              $shopgateOrder
     * @param oxorder                    $oxidOrder
     * @param string                     $payoneTxAction
     */
    protected function generateTransactionStatusEntry(
        $referenceNumber,
        ShopgatePayonePaymentInfos $payonePaymentInfos,
        ShopgateOrder $shopgateOrder,
        $oxidOrder,
        $payoneTxAction
    ) {
        $insertData = array(
            'FCPO_ORDERNR'                    => $oxidOrder->oxorder__oxid->value,
            // payone key - seems to be the same string for all requests but we don't get it via payment infos
            'FCPO_KEY'                        => '',
            'FCPO_TXACTION'                   => $payoneTxAction,
            'FCPO_PORTALID'                   => $payonePaymentInfos->getPortalId(),
            'FCPO_AID'                        => $payonePaymentInfos->getSubAccountId(),
            'FCPO_CLEARINGTYPE'               => $payonePaymentInfos->getClearingType(),
            'FCPO_TXTIME'                     => $shopgateOrder->getCreatedTime('Y-m-d H:i:s'),
            'FCPO_CURRENCY'                   => $shopgateOrder->getCurrency(),
            // userid - we don't get the value via payment infos
            'FCPO_USERID'                     => '',
            'FCPO_ACCESSNAME'                 => '',
            'FCPO_ACCESSCODE'                 => '',
            'FCPO_PARAM'                      => '',
            'FCPO_MODE'                       => $payonePaymentInfos->getMode(),
            'FCPO_PRICE'                      => $shopgateOrder->getAmountComplete(),
            'FCPO_TXID'                       => $payonePaymentInfos->getTxId(),
            'FCPO_REFERENCE'                  => $referenceNumber,
            'FCPO_SEQUENCENUMBER'             => $payonePaymentInfos->getSequenceNumber(),
            'FCPO_COMPANY'                    => $shopgateOrder->getInvoiceAddress()->getCompany(),
            'FCPO_FIRSTNAME'                  => $shopgateOrder->getInvoiceAddress()->getFirstName(),
            'FCPO_LASTNAME'                   => $shopgateOrder->getInvoiceAddress()->getLastName(),
            'FCPO_STREET'                     => $shopgateOrder->getInvoiceAddress()->getStreet1(),
            'FCPO_ZIP'                        => $shopgateOrder->getInvoiceAddress()->getZipcode(),
            'FCPO_CITY'                       => $shopgateOrder->getInvoiceAddress()->getCity(),
            'FCPO_EMAIL'                      => $shopgateOrder->getInvoiceAddress()->getMail(),
            'FCPO_COUNTRY'                    => $shopgateOrder->getInvoiceAddress()->getCountry(),
            'FCPO_SHIPPING_COMPANY'           => $shopgateOrder->getDeliveryAddress()->getCompany(),
            'FCPO_SHIPPING_FIRSTNAME'         => $shopgateOrder->getDeliveryAddress()->getFirstName(),
            'FCPO_SHIPPING_LASTNAME'          => $shopgateOrder->getDeliveryAddress()->getLastName(),
            'FCPO_SHIPPING_STREET'            => $shopgateOrder->getDeliveryAddress()->getStreet1(),
            'FCPO_SHIPPING_ZIP'               => $shopgateOrder->getDeliveryAddress()->getZipcode(),
            'FCPO_SHIPPING_CITY'              => $shopgateOrder->getDeliveryAddress()->getCity(),
            'FCPO_SHIPPING_COUNTRY'           => $shopgateOrder->getDeliveryAddress()->getCountry(),
            'FCPO_BANKCOUNTRY'                => '',
            'FCPO_BANKACCOUNT'                => '',
            'FCPO_BANKCODE'                   => '',
            'FCPO_BANKACCOUNTHOLDER'          => '',
            'FCPO_CARDEXPIREDATE'             => $this->generatePayoneCreditCardExpiryDate($payonePaymentInfos),
            'FCPO_CARDTYPE'                   => $payonePaymentInfos->getCcType(),
            'FCPO_CARDPAN'                    => $payonePaymentInfos->getCcMaskedNumber(),
            // customerid - we don't get the value via payment infos
            'FCPO_CUSTOMERID'                 => '',
            'FCPO_BALANCE'                    => $payoneTxAction == self::PAYONE_TX_ACTION_APPOINTED
                ? $shopgateOrder->getAmountComplete()
                : '0',
            'FCPO_RECEIVABLE'                 => $shopgateOrder->getAmountComplete(),
            'FCPO_CLEARING_BANKACCOUNTHOLDER' => '',
            'FCPO_CLEARING_BANKACCOUNT'       => '',
            'FCPO_CLEARING_BANKCODE'          => '',
            'FCPO_CLEARING_BANKNAME'          => '',
            'FCPO_CLEARING_BANKBIC'           => '',
            'FCPO_CLEARING_BANKIBAN'          => '',
            'FCPO_CLEARING_LEGALNOTE'         => '',
            'FCPO_CLEARING_DUEDATE'           => '',
            'FCPO_CLEARING_REFERENCE'         => '',
            'FCPO_CLEARING_INSTRUCTIONNOTE'   => '',
        );

        $query = "INSERT INTO fcpotransactionstatus (" . implode(',', array_keys($insertData)) . ") 
				  VALUES ('" . implode("','", array_values($insertData)) . "')";
        oxDb::getDb()->Execute($query);
    }

    /**
     * Shopgate sends the credit card expiry year with 4 positions. This functions trims the year to just the last 2
     * digits. The month gets a leading zero in case the length of the string for month is just 1.
     *
     * @param ShopgatePayonePaymentInfos $payonePaymentInfos
     *
     * @return string Year . Month (e.g. 1605 which stands for May 2016)
     */
    public function generatePayoneCreditCardExpiryDate(ShopgatePayonePaymentInfos $payonePaymentInfos)
    {
        if (!$payonePaymentInfos->getCcExpiryYear() || !$payonePaymentInfos->getCcExpiryMonth()) {
            return '';
        }

        $expiryMonth = $payonePaymentInfos->getCcExpiryMonth();
        if (strlen($expiryMonth) == 1) {
            $expiryMonth = '0' . $expiryMonth;
        }

        return substr($payonePaymentInfos->getCcExpiryYear(), 2) . $expiryMonth;
    }

    /**
     * Generates a reference number or re-uses the old one
     *
     * @return string | int
     */
    public function getRefNumber()
    {
        if ($this->refNumber !== null) {
            return $this->refNumber;
        }

        return $this->refNumber = $this->generateRefNr();
    }

    /**
     * Creates a new reference number or re-uses the old
     *
     * @return string | int
     */
    protected function generateRefNr()
    {
        /** @var fcpoRequest $request */
        $request = oxNew('fcpoRequest');
        if (method_exists($request, 'getRefNr')) {
            return $request->getRefNr();
        }

        return $this->getRefNrForOlderVersions();
    }

    /**
     * @return oxModule
     */
    public function getPayoneModule()
    {
        /** @var OxModule $module */
        $module = oxNew('oxModule');
        $module->load('fcpayone');

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $module;
    }

    /**
     * Customer can decline the credit check. We should set it per default to 0 because we don't get any information in
     * payment infos In oxid a payment method could be set up to have a credit check, but if the customer declines the
     * credit check this value
     * 'fcpoordernotchecked' is set to 1
     *
     * @return oxField
     */
    protected function isCreditCheckDeclined()
    {
        return new oxField(0, oxField::T_RAW);
    }

    /**
     * Below Payone module v2.0 this method is protected, had to copy it
     * and re-write deprecated method usage, @see PDO::quote()
     *
     * @return string
     */
    protected function getRefNrForOlderVersions()
    {
        $db         = oxDb::getDb();
        $sRawPrefix = (string)marm_shopgate::getOxConfig()->getConfigParam('sFCPORefPrefix');
        $sPrefix    = $db->quote($sRawPrefix);
        $sQuery     = "SELECT MAX(fcpo_refnr) FROM fcporefnr WHERE fcpo_refprefix = {$sPrefix}";
        $iMaxRefNr  = $db->GetOne($sQuery);
        $iRefNr     = (int)$iMaxRefNr + 1;
        $query      =
            "INSERT INTO fcporefnr (fcpo_refnr, fcpo_txid, fcpo_refprefix)  VALUES ('{$iRefNr}', '', {$sPrefix})";
        $db->Execute($query);

        return $sRawPrefix . $iRefNr;
    }

    /**
     * Updates transaction id of the order after ref number was generated earlier
     */
    protected function updatePayoneOrderTransactionId()
    {
        oxDb::getDb()->Execute(
            "UPDATE fcporefnr SET fcpo_txid = '{$this->paymentInfos->getTxId()}' WHERE fcpo_refnr = '{$this->getRefNumber()}'"
        );
    }

    /**
     * @param int                        $refNumber
     * @param ShopgatePayonePaymentInfos $payonePaymentInfos
     * @param ShopgateOrder              $shopgateOrder
     * @param oxorder                    $oxidOrder
     */
    protected function generateTransactionStatus(
        $refNumber,
        ShopgatePayonePaymentInfos $payonePaymentInfos,
        ShopgateOrder $shopgateOrder,
        $oxidOrder
    ) {
        // generate Transaction for authorization ipn
        $this->generateTransactionStatusEntry(
            $refNumber,
            $payonePaymentInfos,
            $shopgateOrder,
            $oxidOrder,
            self::PAYONE_TX_ACTION_APPOINTED
        );

        if ($payonePaymentInfos->getRequestType() === ShopgatePayonePaymentInfos::REQUEST_TYPE_AUTH) {
            // generate Transaction for paid ipn
            $this->generateTransactionStatusEntry(
                $refNumber,
                $payonePaymentInfos,
                $shopgateOrder,
                $oxidOrder,
                self::PAYONE_TX_ACTION_PAID
            );
        }
    }
}
