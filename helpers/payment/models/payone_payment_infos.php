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

class ShopgatePayonePaymentInfos
{
    const REQUEST_TYPE_PREAUTH = 'preauthorization';
    const REQUEST_TYPE_AUTH    = 'authorization';

    /** @var int */
    private $portalId;

    /** @var int */
    private $subAccountId;

    /** @var int */
    private $txId;

    /** @var string */
    private $requestType;

    /** @var string */
    private $mode;

    /** @var string */
    private $responseStatus;

    /** @var int */
    private $merchantId;

    /** @var string */
    private $clearingType;

    /** @var int */
    private $referenceNumber;

    /** @var string */
    private $ccHolder;

    /** @var string */
    private $ccMaskedNumber;

    /** @var string */
    private $ccType;

    /** @var int */
    private $ccExpiryYear;

    /** @var int */
    private $ccExpiryMonth;

    /** @var int */
    private $sequenceNumber;

    /**
     * @param array $paymentInfos
     * @param int   $referenceNumber
     */
    public function __construct(array $paymentInfos, $referenceNumber)
    {
        $this->extractPaymentInfos($paymentInfos);
        $this->referenceNumber = $referenceNumber;
    }

    /**
     * @param array $paymentInfos
     */
    private function extractPaymentInfos(array $paymentInfos)
    {
        $mapping = array(
            'txid'            => 'txId',
            'portalid'        => 'portalId',
            'aid'             => 'subAccountId',
            'request_type'    => 'requestType',
            'mode'            => 'mode',
            'status'          => 'responseStatus',
            'userid'          => 'userId',
            'clearing_type'   => 'clearingType',
            'mid'             => 'merchantId',
            'sequence_number' => 'sequenceNumber',
        );

        $this->mapValues($paymentInfos, $mapping);

        if (!empty($paymentInfos['credit_card'])) {
            $creditCardMapping = array(
                'holder'        => 'ccHolder',
                'masked_number' => 'ccMaskedNumber',
                'type'          => 'ccType',
                'expiry_year'   => 'ccExpiryYear',
                'expiry_month'  => 'ccExpiryMonth',
            );
            $this->mapValues($paymentInfos['credit_card'], $creditCardMapping);

            if (isset($this->ccType)) {
                $this->ccType = $this->mapShopgateCreditCardTypeToPayoneCreditCardType($this->ccType);
            }
        }
    }

    /**
     * @param array $paymentInfos
     * @param array $mapping
     */
    private function mapValues(array $paymentInfos, array $mapping)
    {
        foreach ($mapping as $paymentInfosKey => $classVariableName) {
            if (!isset($paymentInfos[$paymentInfosKey])) {
                continue;
            }
            $this->{$classVariableName} = $paymentInfos[$paymentInfosKey];
        }
    }

    /**
     * This method maps Shopgate credit card types to payone credit card types.
     * In case 'china_union_pay' or an unknown provider was returned the returned type is an empty string
     *
     * @param string $shopgateCreditType
     *
     * @return string
     */
    private function mapShopgateCreditCardTypeToPayoneCreditCardType($shopgateCreditType)
    {
        $mapping = array(
            'visa'             => 'V',
            'american_express' => 'A',
            'mastercard'       => 'M',
            'jcb'              => 'J',
            'discover'         => 'C',
            'china_union_pay'  => '', // it seems Payone don't support this payment provider
            'diners_club'      => 'D',
            'maestro'          => 'O', //maestro international = O, maestro UK = U
            'carte_bleue'      => 'B',
        );

        if (!isset($mapping[$shopgateCreditType])) {
            return '';
        }

        return $mapping[$shopgateCreditType];
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return array
     */
    public function getRequestParameter(ShopgateOrder $shopgateOrder)
    {
        return array(
            'mid'                => $this->merchantId,
            'portalid'           => $this->portalId,
            'key'                => '',//d286b16bb78ea14994073e8f8ad
            'encoding'           => 'UTF-8',
            'integrator_name'    => 'shopgate',
            'integrator_version' => 1,
            'solution_name'      => 'shopgate_s',
            'solution_version'   => 1,
            'request'            => $this->requestType,
            'mode'               => $this->mode,
            'aid'                => $this->subAccountId,
            'reference'          => $this->referenceNumber,
            'amount'             => $shopgateOrder->getAmountComplete(),
            'currency'           => $shopgateOrder->getCurrency(),
            'customerid'         => '',//31936
            'salutation'         => $shopgateOrder->getInvoiceAddress()->getGender() == ShopgateCustomer::MALE
                ? 'Herr'
                : 'Frau',
            'gender'             => $shopgateOrder->getInvoiceAddress()->getGender(),
            'firstname'          => $shopgateOrder->getInvoiceAddress()->getFirstName(),
            'lastname'           => $shopgateOrder->getInvoiceAddress()->getLastName(),
            'company'            => $shopgateOrder->getInvoiceAddress()->getCompany(),
            'street'             => $shopgateOrder->getInvoiceAddress()->getStreet1(),
            'zip'                => $shopgateOrder->getInvoiceAddress()->getZipcode(),
            'city'               => $shopgateOrder->getInvoiceAddress()->getCity(),
            'country'            => $shopgateOrder->getInvoiceAddress()->getCountry(),//DE
            'email'              => $shopgateOrder->getMail(),
            'language'           => 'de',
            'ip'                 => $shopgateOrder->getCustomerIp(),
            'clearingtype'       => $this->clearingType,
            'pseudocardpan'      => '',//4100000101887868
            'successurl'         => '',
            'errorurl'           => '',
            'backurl'            => '',
        );
    }

    public function getResponse()
    {
        return array(
            'status' => $this->responseStatus,
            'txid'   => $this->txId,
            'userid' => '',
        );
    }

    /**
     * @return int
     */
    public function getPortalId()
    {
        return $this->portalId;
    }

    /**
     * @param int $portalId
     */
    public function setPortalId($portalId)
    {
        $this->portalId = $portalId;
    }

    /**
     * @return int
     */
    public function getSubAccountId()
    {
        return $this->subAccountId;
    }

    /**
     * @param int $subAccountId
     */
    public function setSubAccountId($subAccountId)
    {
        $this->subAccountId = $subAccountId;
    }

    /**
     * @return int
     */
    public function getTxId()
    {
        return $this->txId;
    }

    /**
     * @param int $txId
     */
    public function setTxId($txId)
    {
        $this->txId = $txId;
    }

    /**
     * @return string
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * @param string $requestType
     */
    public function setRequestType($requestType)
    {
        $this->requestType = $requestType;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return string
     */
    public function getResponseStatus()
    {
        return $this->responseStatus;
    }

    /**
     * @param string $responseStatus
     */
    public function setResponseStatus($responseStatus)
    {
        $this->responseStatus = $responseStatus;
    }

    /**
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * @param int $merchantId
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;
    }

    /**
     * @return string
     */
    public function getClearingType()
    {
        return $this->clearingType;
    }

    /**
     * @param string $clearingType
     */
    public function setClearingType($clearingType)
    {
        $this->clearingType = $clearingType;
    }

    /**
     * @return int
     */
    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }

    /**
     * @param int $referenceNumber
     */
    public function setReferenceNumber($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;
    }

    /**
     * @return string
     */
    public function getCcHolder()
    {
        return $this->ccHolder;
    }

    /**
     * @param string $ccHolder
     */
    public function setCcHolder($ccHolder)
    {
        $this->ccHolder = $ccHolder;
    }

    /**
     * @return string
     */
    public function getCcMaskedNumber()
    {
        return $this->ccMaskedNumber;
    }

    /**
     * @param string $ccMaskedNumber
     */
    public function setCcMaskedNumber($ccMaskedNumber)
    {
        $this->ccMaskedNumber = $ccMaskedNumber;
    }

    /**
     * @return string
     */
    public function getCcType()
    {
        return $this->ccType;
    }

    /**
     * @param string $ccType
     */
    public function setCcType($ccType)
    {
        $this->ccType = $ccType;
    }

    /**
     * @return int
     */
    public function getCcExpiryYear()
    {
        return $this->ccExpiryYear;
    }

    /**
     * @param int $ccExpiryYear
     */
    public function setCcExpiryYear($ccExpiryYear)
    {
        $this->ccExpiryYear = $ccExpiryYear;
    }

    /**
     * @return int
     */
    public function getCcExpiryMonth()
    {
        return $this->ccExpiryMonth;
    }

    /**
     * @param int $ccExpiryMonth
     */
    public function setCcExpiryMonth($ccExpiryMonth)
    {
        $this->ccExpiryMonth = $ccExpiryMonth;
    }

    /**
     * @return int
     */
    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }

    /**
     * @param int $sequenceNumber
     */
    public function setSequenceNumber($sequenceNumber)
    {
        $this->sequenceNumber = $sequenceNumber;
    }
}
