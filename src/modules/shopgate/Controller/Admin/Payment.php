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
 * @noinspection PhpClassConstantAccessedViaChildClassInspection
 */

namespace Shopgate\Oxid\Controller\Admin;

use Shopgate\Oxid\Core\Module;
use OxidEsales\Eshop\Application\Model\Payment as oxPayment;
use OxidEsales\Eshop\Core\Field;
use ShopgateOrder as ShopgatePluginApiOrder;

/**
 * Admin controller for Shopgate config tab
 */
class Payment extends Payment_parent
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
        Module::getInstance()->init();

        $return = parent::render();

        $soxId = $this->_aViewData['oxid'] = $this->getEditObjectId();
        /** @var oxPayment $oxPayment */
        $oxPayment = oxNew('oxPayment');
        $oxPayment->load($soxId);

        $this->_aViewData['edit'] = $oxPayment;
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
            $this->isError = true;
            $this->errorMessage = "Cannot save - The field 'shopgate_payment_method' is missing";

            return;
        }

        $sOxid = Module::getRequestParameter('oxid');
        $sShopgatePaymentMethodId = Module::getRequestParameter('shopgate_payment_method_id');

        $oxPayment->load($sOxid);

        $oxPayment->oxpayments__shopgate_payment_method = new Field($sShopgatePaymentMethodId, Field::T_RAW);
        $oxPayment->save();
    }

    /**
     * @return array
     */
    public function getPaymentMethodList()
    {
        return array(
            ShopgatePluginApiOrder::PREPAY => array(
                ShopgatePluginApiOrder::PREPAY,
            ),
            ShopgatePluginApiOrder::CC => array(
                ShopgatePluginApiOrder::CC,
                ShopgatePluginApiOrder::AUTHN_CC,
                ShopgatePluginApiOrder::BCLEPDQ_CC,
                ShopgatePluginApiOrder::BNSTRM_CC,
                ShopgatePluginApiOrder::BRAINTR_CC,
                ShopgatePluginApiOrder::CHASE_CC,
                ShopgatePluginApiOrder::CMPTOP_CC,
                ShopgatePluginApiOrder::CONCAR_CC,
                ShopgatePluginApiOrder::CRDSTRM_CC,
                ShopgatePluginApiOrder::CREDITCARD,
                ShopgatePluginApiOrder::CYBRSRC_CC,
                ShopgatePluginApiOrder::DRCPAY_CC,
                ShopgatePluginApiOrder::DTCASH_CC,
                ShopgatePluginApiOrder::DT_CC,
                ShopgatePluginApiOrder::EFSNET_CC,
                ShopgatePluginApiOrder::ELAVON_CC,
                ShopgatePluginApiOrder::EPAY_CC,
                ShopgatePluginApiOrder::EWAY_CC,
                ShopgatePluginApiOrder::EXACT_CC,
                ShopgatePluginApiOrder::FRSTDAT_CC,
                ShopgatePluginApiOrder::GAMEDAY_CC,
                ShopgatePluginApiOrder::GARANTI_CC,
                ShopgatePluginApiOrder::GESTPAY_CC,
                ShopgatePluginApiOrder::HDLPAY_CC,
                ShopgatePluginApiOrder::HIPAY,
                ShopgatePluginApiOrder::HITRUST_CC,
                ShopgatePluginApiOrder::INSPIRE_CC,
                ShopgatePluginApiOrder::INSTAP_CC,
                ShopgatePluginApiOrder::INTUIT_CC,
                ShopgatePluginApiOrder::IRIDIUM_CC,
                ShopgatePluginApiOrder::LITLE_CC,
                ShopgatePluginApiOrder::MASTPAY_CC,
                ShopgatePluginApiOrder::MERESOL_CC,
                ShopgatePluginApiOrder::MERWARE_CC,
                ShopgatePluginApiOrder::MODRPAY_CC,
                ShopgatePluginApiOrder::MONERIS_CC,
                ShopgatePluginApiOrder::MSTPAY_CC,
                ShopgatePluginApiOrder::NELTRAX_CC,
                ShopgatePluginApiOrder::NETBILL_CC,
                ShopgatePluginApiOrder::NETREGS_CC,
                ShopgatePluginApiOrder::NOCHEX_CC,
                ShopgatePluginApiOrder::OGONE_CC,
                ShopgatePluginApiOrder::OPTIMAL_CC,
                ShopgatePluginApiOrder::PAY4ONE_CC,
                ShopgatePluginApiOrder::PAYBOX_CC,
                ShopgatePluginApiOrder::PAYEXPR_CC,
                ShopgatePluginApiOrder::PAYFAST_CC,
                ShopgatePluginApiOrder::PAYFLOW_CC,
                ShopgatePluginApiOrder::PAYJUNC_CC,
                ShopgatePluginApiOrder::PAYONE_CC,
                ShopgatePluginApiOrder::PAYZEN_CC,
                ShopgatePluginApiOrder::PLUGNPL_CC,
                ShopgatePluginApiOrder::PP_WSPP_CC,
                ShopgatePluginApiOrder::PSIGATE_CC,
                ShopgatePluginApiOrder::PSL_CC,
                ShopgatePluginApiOrder::PXPAY_CC,
                ShopgatePluginApiOrder::QUIKPAY_CC,
                ShopgatePluginApiOrder::REALEX_CC,
                ShopgatePluginApiOrder::SAGEPAY_CC,
                ShopgatePluginApiOrder::SAGE_CC,
                ShopgatePluginApiOrder::SAMURAI_CC,
                ShopgatePluginApiOrder::SCPTECH_CC,
                ShopgatePluginApiOrder::SCP_AU_CC,
                ShopgatePluginApiOrder::SECPAY_CC,
                ShopgatePluginApiOrder::SG_CC,
                ShopgatePluginApiOrder::SIX_CC,
                ShopgatePluginApiOrder::SKIPJCK_CC,
                ShopgatePluginApiOrder::SKRILL_CC,
                ShopgatePluginApiOrder::STRIPE_CC,
                ShopgatePluginApiOrder::TELECSH_CC,
                ShopgatePluginApiOrder::TRNSFST_CC,
                ShopgatePluginApiOrder::TRUSTCM_CC,
                ShopgatePluginApiOrder::USAEPAY_CC,
                ShopgatePluginApiOrder::VALITOR_CC,
                ShopgatePluginApiOrder::VERIFI_CC,
                ShopgatePluginApiOrder::VIAKLIX_CC,
                ShopgatePluginApiOrder::WCARDS_CC,
                ShopgatePluginApiOrder::WIRECRD_CC,
                ShopgatePluginApiOrder::WLDPDIR_CC,
                ShopgatePluginApiOrder::WLDPOFF_CC,

            ),
            ShopgatePluginApiOrder::INVOICE => array(
                ShopgatePluginApiOrder::INVOICE,
                ShopgatePluginApiOrder::KLARNA_INV,
                ShopgatePluginApiOrder::BILLSAFE,
                ShopgatePluginApiOrder::MSTPAY_INV,
                ShopgatePluginApiOrder::PAYMRW_INV,
                ShopgatePluginApiOrder::PAYONE_INV,
                ShopgatePluginApiOrder::PAYOL_INV,
            ),
            ShopgatePluginApiOrder::DEBIT => array(
                ShopgatePluginApiOrder::DEBIT,
                ShopgatePluginApiOrder::PAYMRW_DBT,
                ShopgatePluginApiOrder::PAYONE_DBT,
            ),
            ShopgatePluginApiOrder::COD => array(
                ShopgatePluginApiOrder::COD,
            ),
            ShopgatePluginApiOrder::AMAZON_PAYMENT => array(
                ShopgatePluginApiOrder::AMAZON_PAYMENT,
            ),
            ShopgatePluginApiOrder::CNB => array(
                ShopgatePluginApiOrder::CNB,
            ),
            ShopgatePluginApiOrder::PAYPAL => array(
                ShopgatePluginApiOrder::PAYPAL,
                ShopgatePluginApiOrder::MASTPAY_PP,
                ShopgatePluginApiOrder::SAGEPAY_PP,
            ),
            ShopgatePluginApiOrder::PAYU => array(
                ShopgatePluginApiOrder::PAYU,
            ),
            ShopgatePluginApiOrder::SUE => array(
                ShopgatePluginApiOrder::SUE,
            ),
            ShopgatePluginApiOrder::COLL_STORE => array(
                ShopgatePluginApiOrder::COLL_STORE,
            ),
            ShopgatePluginApiOrder::MERCH_PM => array(
                ShopgatePluginApiOrder::MERCH_PM,
                ShopgatePluginApiOrder::MERCH_PM_2,
                ShopgatePluginApiOrder::MERCH_PM_3,
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
