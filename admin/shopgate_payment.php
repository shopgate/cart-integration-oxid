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
 * Admin controller for Shopgate config tab
 */
class shopgate_payment extends oxAdminDetails
{
    /**
     * shopgate configuration template
     *
     * @var string
     */
    protected $_sThisTemplate = 'shopgate_payment.tpl';

    /**
     * stores array for shopgate config, with information how to display it
     *
     * @var array
     */
    protected $_aShopgateConfig = null;

    /** @var bool */
    protected $isError = false;

    /** @var string */
    protected $errorMessage = "";

    public function render()
    {
        marm_shopgate::getInstance()->init();

        $return = parent::render();

        $soxId = $this->_aViewData['oxid'] = $this->getEditObjectId();
        /** @var oxPayment $oxPayment */
        $oxPayment = oxNew('oxPayment');
        $oxPayment->load($soxId);

        $this->_aViewData['edit']            = $oxPayment;
        $this->_aViewData['payment_methods'] = $this->getPaymentMethodList();

        return $return;
    }

    public function setPaymentMethod()
    {
        /** @var oxPayment $oxPayment */
        $oxPayment = oxNew('oxPayment');

        // Works with oxid 4.3
        $fields = $oxPayment->getSelectFields();
        $fields = preg_replace("/`/", "", $fields);
        $fields = explode(", ", $fields);

        if (!in_array("{$oxPayment->getViewName()}.shopgate_payment_method", $fields)) {
            $this->isError      = true;
            $this->errorMessage = "Cannot save - The field 'shopgate_payment_method' is missing";

            return;
        }

        $sOxid                    = marm_shopgate::getRequestParameter('oxid');
        $sShopgatePaymentMethodId = marm_shopgate::getRequestParameter('shopgate_payment_method_id');

        $oxPayment->load($sOxid);

        $oxPayment->oxpayments__shopgate_payment_method = new oxField($sShopgatePaymentMethodId, oxField::T_RAW);
        $oxPayment->save();
    }

    /**
     * @return array
     */
    public function getPaymentMethodList()
    {
        return array(
            ShopgateOrder::PREPAY         => array(
                ShopgateOrder::PREPAY,
            ),
            ShopgateOrder::CC             => array(
                ShopgateOrder::CC,
                ShopgateOrder::AUTHN_CC,
                ShopgateOrder::BCLEPDQ_CC,
                ShopgateOrder::BNSTRM_CC,
                ShopgateOrder::BRAINTR_CC,
                ShopgateOrder::CHASE_CC,
                ShopgateOrder::CMPTOP_CC,
                ShopgateOrder::CONCAR_CC,
                ShopgateOrder::CRDSTRM_CC,
                ShopgateOrder::CREDITCARD,
                ShopgateOrder::CYBRSRC_CC,
                ShopgateOrder::DRCPAY_CC,
                ShopgateOrder::DTCASH_CC,
                ShopgateOrder::DT_CC,
                ShopgateOrder::EFSNET_CC,
                ShopgateOrder::ELAVON_CC,
                ShopgateOrder::EPAY_CC,
                ShopgateOrder::EWAY_CC,
                ShopgateOrder::EXACT_CC,
                ShopgateOrder::FRSTDAT_CC,
                ShopgateOrder::GAMEDAY_CC,
                ShopgateOrder::GARANTI_CC,
                ShopgateOrder::GESTPAY_CC,
                ShopgateOrder::HDLPAY_CC,
                ShopgateOrder::HIPAY,
                ShopgateOrder::HITRUST_CC,
                ShopgateOrder::INSPIRE_CC,
                ShopgateOrder::INSTAP_CC,
                ShopgateOrder::INTUIT_CC,
                ShopgateOrder::IRIDIUM_CC,
                ShopgateOrder::LITLE_CC,
                ShopgateOrder::MASTPAY_CC,
                ShopgateOrder::MERESOL_CC,
                ShopgateOrder::MERWARE_CC,
                ShopgateOrder::MODRPAY_CC,
                ShopgateOrder::MONERIS_CC,
                ShopgateOrder::MSTPAY_CC,
                ShopgateOrder::NELTRAX_CC,
                ShopgateOrder::NETBILL_CC,
                ShopgateOrder::NETREGS_CC,
                ShopgateOrder::NOCHEX_CC,
                ShopgateOrder::OGONE_CC,
                ShopgateOrder::OPTIMAL_CC,
                ShopgateOrder::PAY4ONE_CC,
                ShopgateOrder::PAYBOX_CC,
                ShopgateOrder::PAYEXPR_CC,
                ShopgateOrder::PAYFAST_CC,
                ShopgateOrder::PAYFLOW_CC,
                ShopgateOrder::PAYJUNC_CC,
                ShopgateOrder::PAYONE_CC,
                ShopgateOrder::PAYZEN_CC,
                ShopgateOrder::PLUGNPL_CC,
                ShopgateOrder::PP_WSPP_CC,
                ShopgateOrder::PSIGATE_CC,
                ShopgateOrder::PSL_CC,
                ShopgateOrder::PXPAY_CC,
                ShopgateOrder::QUIKPAY_CC,
                ShopgateOrder::REALEX_CC,
                ShopgateOrder::SAGEPAY_CC,
                ShopgateOrder::SAGE_CC,
                ShopgateOrder::SAMURAI_CC,
                ShopgateOrder::SCPTECH_CC,
                ShopgateOrder::SCP_AU_CC,
                ShopgateOrder::SECPAY_CC,
                ShopgateOrder::SG_CC,
                ShopgateOrder::SIX_CC,
                ShopgateOrder::SKIPJCK_CC,
                ShopgateOrder::SKRILL_CC,
                ShopgateOrder::STRIPE_CC,
                ShopgateOrder::TELECSH_CC,
                ShopgateOrder::TRNSFST_CC,
                ShopgateOrder::TRUSTCM_CC,
                ShopgateOrder::USAEPAY_CC,
                ShopgateOrder::VALITOR_CC,
                ShopgateOrder::VERIFI_CC,
                ShopgateOrder::VIAKLIX_CC,
                ShopgateOrder::WCARDS_CC,
                ShopgateOrder::WIRECRD_CC,
                ShopgateOrder::WLDPDIR_CC,
                ShopgateOrder::WLDPOFF_CC,

            ),
            ShopgateOrder::INVOICE        => array(
                ShopgateOrder::INVOICE,
                ShopgateOrder::KLARNA_INV,
                ShopgateOrder::BILLSAFE,
                ShopgateOrder::MSTPAY_INV,
                ShopgateOrder::PAYMRW_INV,
                ShopgateOrder::PAYONE_INV,
                ShopgateOrder::PAYOL_INV,
            ),
            ShopgateOrder::DEBIT          => array(
                ShopgateOrder::DEBIT,
                ShopgateOrder::PAYMRW_DBT,
                ShopgateOrder::PAYONE_DBT,
            ),
            ShopgateOrder::COD            => array(
                ShopgateOrder::COD,
            ),
            ShopgateOrder::AMAZON_PAYMENT => array(
                ShopgateOrder::AMAZON_PAYMENT,
            ),
            ShopgateOrder::CNB            => array(
                ShopgateOrder::CNB,
            ),
            ShopgateOrder::PAYPAL         => array(
                ShopgateOrder::PAYPAL,
                ShopgateOrder::MASTPAY_PP,
                ShopgateOrder::SAGEPAY_PP,
            ),
            ShopgateOrder::PAYU           => array(
                ShopgateOrder::PAYU,
            ),
            ShopgateOrder::SUE            => array(
                ShopgateOrder::SUE,
            ),
            ShopgateOrder::COLL_STORE     => array(
                ShopgateOrder::COLL_STORE,
            ),
            ShopgateOrder::MERCH_PM       => array(
                ShopgateOrder::MERCH_PM,
                ShopgateOrder::MERCH_PM_2,
                ShopgateOrder::MERCH_PM_3,
            ),
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
