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

$paypalPlusAutoloaderFile = dirname(__FILE__) . '/../../../oxps/paypalplus/vendor/autoload.php';
if (file_exists($paypalPlusAutoloaderFile)) {
    require_once $paypalPlusAutoloaderFile;
}

class ShopgatePaymentHelperPaypalPlus extends ShopgatePaymentHelper
{
    public function createSpecificData()
    {
        $this->createPaypalPlusPaymentData();
    }

    private function createPaypalPlusPaymentData()
    {
        $paymentInfos = $this->shopgateOrder->getPaymentInfos();

        /** @var oxpsPayPalPlusPaymentData $data */
        $data = oxNew('oxpsPayPalPlusPaymentData');

        $data->setOrderId($this->oxOrder->getId());
        $data->setSaleId($paymentInfos['payment_transaction_id']);
        $data->setPaymentId($paymentInfos['payment_id']);
        $data->setStatus($paymentInfos['status']);
        $data->setDateCreated($this->shopgateOrder->getPaymentTime());
        $data->setTotal($this->shopgateOrder->getAmountComplete());
        $data->setCurrency($this->shopgateOrder->getCurrency());
        $data->setPaymentObject($this->createPayment($paymentInfos));

        $data->save();
    }

    /**
     * @param array $paymentInfos
     *
     * @return \PayPal\Api\Payment|null
     */
    private function createPayment($paymentInfos)
    {
        if (empty($paymentInfos) || !class_exists('\PayPal\Api\Payment')) {
            return null;
        }

        $result = new \PayPal\Api\Payment();
        $result->setId($paymentInfos['payment_id']);
        $result->setPayer($this->createPayer($paymentInfos['payer']));
        $result->setTransactions($this->createTransactions($paymentInfos['transaction_info']));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\Payer|null
     */
    private function createPayer($data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\Payer')) {
            return null;
        }

        $result = new \PayPal\Api\Payer();
        $result->setPaymentMethod($this->getValue($data, 'payment_method'));
        $result->setStatus($this->getValue($data, 'status'));
        $result->setPayerInfo($this->createPayerInfo($this->getValue($data, 'payer_info')));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\PayerInfo|null
     */
    private function createPayerInfo($data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\PayerInfo')) {
            return null;
        }

        $result = new \PayPal\Api\PayerInfo();
        $result->setEmail($this->getValue($data, 'email'));
        $result->setFirstName($this->getValue($data, 'first_name'));
        $result->setLastName($this->getValue($data, 'last_name'));
        $result->setPayerId($this->getValue($data, 'payer_id'));
        $result->setPhone($this->getValue($data, 'phone'));
        $result->setCountryCode($this->getValue($data, 'country_code'));
        $result->setBillingAddress($this->createBillingAddress($this->getValue($data, 'billing_address')));
        $result->setShippingAddress($this->createShippingAddress($this->getValue($data, 'shipping_address')));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\Address|null
     */
    private function createBillingAddress($data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\Address')) {
            return null;
        }

        $result = new \PayPal\Api\Address();
        $result->setLine1($this->getValue($data, 'line1'));
        $result->setCity($this->getValue($data, 'city'));
        $result->setState($this->getValue($data, 'state'));
        $result->setPostalCode($this->getValue($data, 'postal_code'));
        $result->setCountryCode($this->getValue($data, 'country_code'));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\ShippingAddress|null
     */
    private function createShippingAddress($data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\ShippingAddress')) {
            return null;
        }

        $result = new \PayPal\Api\ShippingAddress();
        $result->setRecipientName($this->getValue($data, 'recipient_name'));
        $result->setLine1($this->getValue($data, 'line1'));
        $result->setCity($this->getValue($data, 'city'));
        $result->setState($this->getValue($data, 'state'));
        $result->setPostalCode($this->getValue($data, 'postal_code'));
        $result->setCountryCode($this->getValue($data, 'country_code'));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\Transaction[]
     */
    private function createTransactions($data)
    {
        $result = array();
        foreach ($data as $transactionArray) {
            $transaction = $this->createTransaction($transactionArray);
            if (!empty($transaction)) {
                $result[] = $transaction;
            }
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\Transaction|null
     */
    private function createTransaction($data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\Transaction')) {
            return null;
        }

        $result = new \PayPal\Api\Transaction();
        $result->setAmount($this->createAmount($this->getValue($data, 'amount')));
        $result->setPayee($this->createPayee($this->getValue($data, 'payee')));
        $result->setDescription($this->getValue($data, 'description'));
        $result->setItemList($this->createItemList($this->getValue($data, 'item_list')));

        // Disabled inspection since Paypal's PhpDoc is faulty here.
        // (Says it expects an object when it really expects an array of objects...)
        /** @noinspection PhpParamsInspection */
        $result->setRelatedResources(
            $this->createRelatedResources($this->getValue($data, 'related_resources', array()))
        );

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\Amount|null
     */
    private function createAmount($data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\Amount')) {
            return null;
        }

        $result = new \PayPal\Api\Amount();
        $result->setTotal($this->getValue($data, 'total'));
        $result->setCurrency($this->getValue($data, 'currency'));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\Payee|null
     */
    private function createPayee($data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\Payee')) {
            return null;
        }

        $result = new \PayPal\Api\Payee();
        $result->setMerchantId($this->getValue($data, 'merchant_id'));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\ItemList|null
     */
    private function createItemList($data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\ItemList')) {
            return null;
        }

        $result = new \PayPal\Api\ItemList();
        $result->setItems($this->createItems($this->getValue($data, 'items', array())));
        $result->setShippingAddress($this->createShippingAddress($this->getValue($data, 'shipping_address')));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\Item[]
     */
    private function createItems($data)
    {
        $result = array();
        foreach ($data as $itemArray) {
            $result[] = $this->createItem($itemArray);
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\Item|null
     */
    private function createItem($data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\Item')) {
            return null;
        }

        $result = new \PayPal\Api\Item();
        $result->setName($this->getValue($data, 'name'));
        $result->setSku($this->getValue($data, 'sku'));
        $result->setPrice($this->getValue($data, 'price'));
        $result->setCurrency($this->getValue($data, 'currency'));
        $result->setQuantity($this->getValue($data, 'quantity'));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\RelatedResources[]
     */
    private function createRelatedResources($data)
    {
        $result = array();
        foreach ($data as $relatedResourceArray) {
            $relatedResource = $this->createRelatedResource($relatedResourceArray);
            if (!empty($relatedResource)) {
                $result[] = $relatedResource;
            }
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\RelatedResources|null
     */
    private function createRelatedResource(array $data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\RelatedResources')) {
            return null;
        }

        $result = new \PayPal\Api\RelatedResources();
        $result->setSale($this->createSale($this->getValue($data, 'sale')));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\Sale|null
     */
    private function createSale($data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\Sale')) {
            return null;
        }

        $result = new \PayPal\Api\Sale();
        $result->setId($this->getValue($data, 'id'));
        $result->setState($this->getValue($data, 'state'));
        $result->setAmount($this->createAmount($this->getValue($data, 'amount')));
        $result->setPaymentMode($this->getValue($data, 'payment_mode'));
        $result->setProtectionEligibility($this->getValue($data, 'protection_eligibility'));
        $result->setProtectionEligibilityType($this->getValue($data, 'protection_eligibility_type'));
        $result->setTransactionFee($this->createTransactionFee($this->getValue($data, 'transaction_fee')));
        $result->setReceiptId($this->getValue($data, 'receipt_id'));
        $result->setParentPayment($this->getValue($data, 'parent_payment'));
        $result->setCreateTime($this->getValue($data, 'create_time'));
        $result->setUpdateTime($this->getValue($data, 'update_time'));
        $result->setLinks($this->createLinks($this->getValue($data, 'links', array())));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\Currency|null
     */
    private function createTransactionFee($data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\Currency')) {
            return null;
        }

        $result = new \PayPal\Api\Currency();
        $result->setValue($this->getValue($data, 'value'));
        $result->setCurrency($this->getValue($data, 'currency'));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\Links[]
     */
    private function createLinks($data)
    {
        $result = array();
        foreach ($data as $linkArray) {
            $link = $this->createLink($linkArray);
            if (!empty($link)) {
                $result[] = $link;
            }
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return \PayPal\Api\Links|null
     */
    private function createLink($data)
    {
        if (empty($data) || !class_exists('\PayPal\Api\Links')) {
            return null;
        }

        $result = new \PayPal\Api\Links();
        $result->setHref($this->getValue($data, 'href'));
        $result->setRel($this->getValue($data, 'rel'));
        $result->setMethod($this->getValue($data, 'method'));

        return $result;
    }
}
