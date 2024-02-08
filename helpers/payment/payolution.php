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

class ShopgatePaymentHelperPayolution extends ShopgatePaymentHelper
{
    const PAYOLUTION_STATUS_PRECHECKED      = 'prechecked';
    const PAYOLUTION_STATUS_CREATED         = 'created';
    const PAYOLUTION_STATUS_CANCELLED       = 'cancelled';
    const PAYOLUTION_STATUS_SHIPPED         = 'shipped';
    const PAYOLUTION_STATUS_PARTLY_SHIPPED  = 'partly_shipped';
    const PAYOLUTION_STATUS_REFUNDED        = 'refunded';
    const PAYOLUTION_STATUS_PARTLY_REFUNDED = 'partly_refunded';

    public function loadOrderPaymentInfos()
    {
        parent::loadOrderPaymentInfos();

        $paymentInfos  = $this->shopgateOrder->getPaymentInfos();
        $isInstallment = ($this->shopgateOrder->getPaymentMethod() == ShopgateOrder::PAYOL_INS);

        $this->oxOrder->oxorder__payo_status        = new oxField(
            $this->getPayolutionStatus($this->shopgateOrder),
            oxField::T_RAW
        );
        $this->oxOrder->oxorder__payo_unique_id     = new oxField($paymentInfos['unique_id'], oxField::T_RAW);
        $this->oxOrder->oxorder__payo_ip            = new oxField($paymentInfos['ip'], oxField::T_RAW);
        $this->oxOrder->oxorder__payo_preauth_id    = new oxField($paymentInfos['preauth_id'], oxField::T_RAW);
        $this->oxOrder->oxorder__payo_capture_id    = new oxField($paymentInfos['capture_id'], oxField::T_RAW);
        $this->oxOrder->oxorder__payo_reference_id  = new oxField($paymentInfos['reference_id'], oxField::T_RAW);
        $this->oxOrder->oxorder__payo_preauth_price = new oxField(
            $this->shopgateOrder->getAmountComplete(),
            oxField::T_RAW
        );
        if ($isInstallment) {
            $this->oxOrder->oxorder__payo_refund_available = new oxField(
                $this->shopgateOrder->getAmountComplete(),
                oxField::T_RAW
            );
            $this->oxOrder->oxorder__payo_captured_price   = new oxField(
                $this->shopgateOrder->getAmountComplete(),
                oxField::T_RAW
            );
        }
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     *
     * @return string
     */
    private function getPayolutionStatus(ShopgateOrder $shopgateOrder)
    {
        if ($shopgateOrder->getIsPaid() && $shopgateOrder->getIsShippingCompleted()) {
            return self::PAYOLUTION_STATUS_SHIPPED;
        }
        $paymentInfos = $shopgateOrder->getPaymentInfos();
        switch ($paymentInfos['status']) {
            case 'REJECTED_BANK':
                return self::PAYOLUTION_STATUS_CANCELLED;
            case 'NEW':
            case 'PENDING':
            case 'WAITING':
            default:
                return self::PAYOLUTION_STATUS_CREATED;
        }
    }

    public function createOxUserPayment()
    {
        $oxUserPayment = parent::createOxUserPayment();

        $paymentInfos                           = $this->shopgateOrder->getPaymentInfos();
        $isInstallment                          = ($this->shopgateOrder->getPaymentMethod(
            ) == ShopgateOrder::PAYOL_INS);
        $info                                   = array(
            'payolution_installment_birthday'       => $this->shopgateOrder->getInvoiceAddress()->getBirthday(),
            'payolution_installment_terms'          => $isInstallment
                ? 1
                : 0,
            'payolution_installment_privacy'        => $isInstallment
                ? 1
                : 0,
            'payolution_installment_iban'           => $isInstallment
                ? $paymentInfos['bank_data']['bank_iban']
                : '',
            'payolution_installment_bic'            => $isInstallment
                ? $paymentInfos['bank_data']['bank_bic']
                : '',
            'payolution_installment_account_holder' => $isInstallment
                ? $paymentInfos['bank_data']['bank_holder']
                : '',
            'payolution_installment_period'         => $isInstallment
                ? $paymentInfos['plan']['duration']
                : '',
            'payolution_b2c_terms'                  => $isInstallment
                ? 0
                : 1,
            'payolution_b2c_privacy'                => $isInstallment
                ? 0
                : 1,
            'payolution_b2c_birthday'               => $this->shopgateOrder->getInvoiceAddress()->getBirthday(),
            // The following fields are always empty (since we don't support Payolution B2B), but should be present anyway.
            'payolution_b2b_ust_id'                 => '',
            'payolution_b2b_terms'                  => '',
            'payolution_b2b_privacy'                => '',
            'payolution_b2b_birthday'               => '',
        );
        $oxUserPayment->oxuserpayments__oxvalue = new oxField(
            marm_shopgate::getOxUtils()->assignValuesToText($info),
            oxField::T_RAW
        );

        $oxUserPayment->save();

        return $oxUserPayment;
    }

    public function createSpecificData()
    {
        $this->createPayolutionHistoryEntry();
        if ($this->oxidPaymentId == ShopgatePaymentHelper::PAYMENT_ID_PAYOLUTION_INSTALLMENT) {
            $this->createPayolutionOrdershipmentsEntries();
        }
    }

    private function createPayolutionHistoryEntry()
    {
        if (class_exists('Payolution_History')) {
            $historyValues = array(
                'price'    => $this->shopgateOrder->getAmountComplete(),
                'currency' => $this->shopgateOrder->getCurrency(),
            );
            /** @var Payolution_History $payolutionHistory */
            $payolutionHistory                               = oxNew('Payolution_History');
            $payolutionHistory->payo_history__order_id       = new oxField($this->oxOrder->getId(), oxField::T_RAW);
            $payolutionHistory->payo_history__status         = new oxField(
                $this->getPayolutionStatus($this->shopgateOrder),
                oxField::T_RAW
            );
            $payolutionHistory->payo_history__history_values = new oxField(
                $this->jsonEncode($historyValues),
                oxField::T_RAW
            );
            $payolutionHistory->save();
        }
    }

    private function createPayolutionOrdershipmentsEntries()
    {
        /** @var oxOrderArticle $oxOrderArticle */
        foreach ($this->oxOrder->getOrderArticles() as $oxOrderArticle) {
            $oxid   = $this->oxOrder->getId();
            $itemId = $oxOrderArticle->getId();
            $amount = $oxOrderArticle->oxorderarticles__oxamount;
            $sql    = "INSERT INTO `payo_ordershipments` (`oxid`, `item_id`, `amount`) VALUES ('$oxid', '$itemId', $amount);";
            marm_shopgate::dbExecute($sql);
        }
    }
}
