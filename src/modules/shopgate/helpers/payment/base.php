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
class ShopgatePaymentHelper extends ShopgateObject
{
    const PAY_TYPE_KEY                      = 'oxid';
    const PAY_MODULE_KEY                    = 'module_id';
    const PAYMENT_ID_MOBILE_PAYMENT         = 'oxmobile_payment';
    const PAYMENT_ID_CREDITCARD             = 'oxidcreditcard';
    const PAYMENT_ID_CASH_ON_DELIVERY       = 'oxidcashondel';
    const PAYMENT_ID_DEBITNOTE              = 'oxiddebitnote';
    const PAYMENT_ID_INVOICE                = 'oxidinvoice';
    const PAYMENT_ID_PAYOLUTION_INVOICE_B2C = 'payolution_invoice_b2c';
    const PAYMENT_ID_PAYOLUTION_INSTALLMENT = 'payolution_installment';
    const PAYMENT_ID_PAYPAL                 = 'oxidpaypal';
    const PAYMENT_ID_PAYPAL_PLUS            = 'oxpspaypalplus';
    const PAYMENT_ID_PREPAYMENT             = 'oxidpayadvance';
    const PAYMENT_ID_SHOPGATE               = 'oxshopgate';
    const PAYMENT_ID_BILLSAFE               = 'mo_billsafe';
    const PAYMENT_ID_PO_PRP                 = 'fcpopayadvance';
    const PAYMENT_ID_PO_KLV                 = 'fcpoklarna';
    const PAYMENT_ID_PO_INV                 = 'fcpoinvoice';
    const PAYMENT_ID_PO_CC                  = 'fcpocreditcard';
    const PAYMENT_ID_PO_PP                  = 'fcpopaypal';
    const PAYMENT_ID_PO_SUE                 = 'fcpoonlineueberweisung';
    const PAYMENT_ID_PO_DBT                 = 'fcpodebitnote';
    const PAYMENT_ID_PO_CASH_ON_DELIVERY    = 'fcpocashondel';
    const PAYMENT_ID_PO_COMMERZ_FINANZ      = 'fcpocommerzfinanz';
    const PAYMENT_ID_PO_BILLSAFE            = 'fcpobillsafe';

    protected static $defaultMapping = array(
        ShopgateOrder::CC         => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_CREDITCARD),
        ShopgateOrder::COD        => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_CASH_ON_DELIVERY),
        ShopgateOrder::DEBIT      => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_DEBITNOTE),
        ShopgateOrder::INVOICE    => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_INVOICE),
        ShopgateOrder::PAYPAL     => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_PAYPAL),
        ShopgateOrder::PPAL_PLUS  => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_PAYPAL_PLUS),
        ShopgateOrder::PREPAY     => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_PREPAYMENT),
        ShopgateOrder::SHOPGATE   => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_SHOPGATE),
        ShopgateOrder::PAYOL_INS  => array(
            self::PAY_TYPE_KEY   => self::PAYMENT_ID_PAYOLUTION_INSTALLMENT,
            self::PAY_MODULE_KEY => 'payolution',
        ),
        ShopgateOrder::PAYOL_INV  => array(
            self::PAY_TYPE_KEY   => self::PAYMENT_ID_PAYOLUTION_INVOICE_B2C,
            self::PAY_MODULE_KEY => 'payolution',
        ),
        ShopgateOrder::BILLSAFE   => array(
            self::PAY_TYPE_KEY   => self::PAYMENT_ID_BILLSAFE,
            self::PAY_MODULE_KEY => 'mo_billsafe',
        ),
        ShopgateOrder::PAYONE_CC  => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_PO_CC),
        ShopgateOrder::PAYONE_PRP => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_PO_PRP),
        ShopgateOrder::PAYONE_INV => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_PO_INV),
        ShopgateOrder::PAYONE_KLV => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_PO_KLV),
        ShopgateOrder::PAYONE_PP  => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_PO_PP),
        ShopgateOrder::PAYONE_SUE => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_PO_SUE),
        ShopgateOrder::PAYONE_DBT => array(self::PAY_TYPE_KEY => self::PAYMENT_ID_PO_DBT),

    );

    /** @var $shopgateOrder */
    protected $shopgateOrder;

    /** @var oxOrder */
    protected $oxOrder;

    /** @var string */
    protected $oxidPaymentId;

    /**
     * @param ShopgateOrder $shopgateOrder
     * @param oxOrder       $oxOrder
     * @param string        $paymentId
     */
    protected function __construct(ShopgateOrder $shopgateOrder, oxOrder $oxOrder, $paymentId)
    {
        $this->shopgateOrder = $shopgateOrder;
        $this->oxOrder       = $oxOrder;
        $this->oxidPaymentId = $paymentId;
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     * @param oxOrder       $oxOrder
     *
     * @return ShopgatePaymentHelper
     */
    public static function createInstance(ShopgateOrder $shopgateOrder, oxOrder $oxOrder)
    {
        $oxidPaymentId = self::findOxidPaymentId($shopgateOrder);

        $shopgatePaymentHelper = null;
        switch ($oxidPaymentId) {
            case self::PAYMENT_ID_DEBITNOTE:
                require_once dirname(__FILE__) . '/debit.php';

                $shopgatePaymentHelper = new ShopgatePaymentHelperDebit($shopgateOrder, $oxOrder, $oxidPaymentId);
                break;
            case self::PAYMENT_ID_PAYOLUTION_INVOICE_B2C:
            case self::PAYMENT_ID_PAYOLUTION_INSTALLMENT:
                require_once dirname(__FILE__) . '/payolution.php';

                $shopgatePaymentHelper = new ShopgatePaymentHelperPayolution($shopgateOrder, $oxOrder, $oxidPaymentId);
                break;
            case self::PAYMENT_ID_PAYPAL:
                require_once dirname(__FILE__) . '/paypal.php';

                $shopgatePaymentHelper = new ShopgatePaymentHelperPaypal($shopgateOrder, $oxOrder, $oxidPaymentId);
                break;
            case self::PAYMENT_ID_PAYPAL_PLUS:
                require_once dirname(__FILE__) . '/paypalplus.php';

                $shopgatePaymentHelper = new ShopgatePaymentHelperPaypalPlus($shopgateOrder, $oxOrder, $oxidPaymentId);
                break;
            case self::PAYMENT_ID_PO_CC:
            case self::PAYMENT_ID_PO_PP:
            case self::PAYMENT_ID_PO_SUE:
            case self::PAYMENT_ID_PO_PRP:
            case self::PAYMENT_ID_PO_INV:
            case self::PAYMENT_ID_PO_KLV:
            case self::PAYMENT_ID_PO_DBT:
                $shopgatePaymentHelper = self::getPayoneHelper($shopgateOrder, $oxOrder, $oxidPaymentId);
                break;
            default:
                $shopgatePaymentHelper = new ShopgatePaymentHelper($shopgateOrder, $oxOrder, $oxidPaymentId);
                break;
        }

        return $shopgatePaymentHelper;
    }

    /**
     * @param ShopgateCartBase $shopgateOrder
     *
     * @return string
     */
    public static function findOxidPaymentId(ShopgateCartBase $shopgateOrder)
    {
        /** @var oxCountry $oxCountry */
        $oxCountry = oxNew('oxCountry');
        /** @var oxPayment $oxPayment */
        $oxPayment          = oxNew('oxPayment');
        $shopgatePaymentKey = $shopgateOrder->getPaymentMethod();

        if ($shopgateOrder->getDeliveryAddress()) {
            $country = $shopgateOrder->getDeliveryAddress()->getCountry();
        } elseif ($shopgateOrder->getInvoiceAddress()) {
            $country = $shopgateOrder->getInvoiceAddress()->getCountry();
        }

        // try to fetch manually mapped payment method from database
        if (isset($country)) {
            /** @noinspection SqlResolve */
            $qry    = "SELECT p.oxid
				FROM `{$oxPayment->getViewName()}` p
				LEFT JOIN `oxobject2payment` o2p ON ( o2p.oxpaymentid = p.oxid )
				LEFT JOIN `{$oxCountry->getViewName()}` c ON ( o2p.oxobjectid = c.oxid )
				WHERE `p`.`shopgate_payment_method` = ?
				  AND ( `c`.`OXISOALPHA2` = ? OR o2p.oxid IS NULL )";
            $result = marm_shopgate::dbGetOne($qry, array($shopgatePaymentKey, $country));
            if (!empty($result)) {
                return $result;
            }
        }

        // if no manually mapped entry found, fall back to default mapping
        if (
            isset(self::$defaultMapping[$shopgatePaymentKey]) && $oxPayment->load(
                self::$defaultMapping[$shopgatePaymentKey][self::PAY_TYPE_KEY]
            )
        ) {
            if (class_exists('oxModule')) {
                /** @var oxModule $oxModule */
                $oxModule = oxNew('oxModule');
            }
            if (empty($oxModule)
                || !isset(self::$defaultMapping[$shopgatePaymentKey][self::PAY_MODULE_KEY])
                || $oxModule->load(self::$defaultMapping[$shopgatePaymentKey][self::PAY_MODULE_KEY])
            ) {
                return self::$defaultMapping[$shopgatePaymentKey][self::PAY_TYPE_KEY];
            }
        }

        return self::PAYMENT_ID_MOBILE_PAYMENT;
    }

    /**
     * @param ShopgateOrder $shopgateOrder
     * @param oxOrder       $oxOrder
     * @param string        $oxidPaymentId
     *
     * @return ShopgatePaymentHelperPayoneUtility
     */
    private static function getPayoneHelper(ShopgateOrder $shopgateOrder, oxOrder $oxOrder, $oxidPaymentId)
    {
        $method   = $shopgateOrder->getPaymentMethod();
        $filePath = str_replace('_', '/', strtolower($method));
        $filePath = dirname(__FILE__) . '/' . $filePath . '.php';
        require_once dirname(__FILE__) . '/payone/utility.php';
        require_once dirname(__FILE__) . '/models/payone_payment_infos.php';
        if (file_exists($filePath)) {
            require_once $filePath;

            $class = 'ShopgatePaymentHelper' . ShopgatePaymentHelper::paymentMethodToCamelCase($method);
            if (class_exists($class)) {
                return new $class($shopgateOrder, $oxOrder, $oxidPaymentId);
            }
        }

        return new ShopgatePaymentHelperPayoneUtility($shopgateOrder, $oxOrder, $oxidPaymentId);
    }

    public function loadOrderPaymentInfos()
    {
        $this->oxOrder->oxorder__oxpaymenttype = new oxField($this->oxidPaymentId, oxField::T_RAW);
        $this->oxOrder->oxorder__oxpaycost     = new oxField(
            $this->shopgateOrder->getAmountShopPayment(),
            oxField::T_RAW
        );
    }

    /**
     * @return oxUserPayment
     */
    public function createOxUserPayment()
    {
        /** @var oxUserPayment $oxUserPayment */
        $oxUserPayment = oxNew('oxUserPayment');
        if (!empty($this->oxOrder->oxorder__oxpaymentid->value)) {
            $oxUserPayment->load($this->oxOrder->oxorder__oxpaymentid->value);
        }
        $oxUserPayment->oxuserpayments__oxpaymentsid = new oxField($this->oxidPaymentId, oxField::T_RAW);
        $oxUserPayment->oxuserpayments__oxuserid     = new oxField($this->oxOrder->getUser()->getId(), oxField::T_RAW);
        $oxUserPayment->save();

        $this->oxOrder->oxorder__oxpaymentid = new oxField($oxUserPayment->getId(), oxField::T_RAW);

        return $oxUserPayment;
    }

    /**
     * Override this if the payment method has specific additional data that needs to be inserted into the database
     */
    public function createSpecificData()
    {
    }

    /**
     * Returns array value for given key if exists.
     * Otherwise defaults to $default.
     *
     * @param array  $array
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getValue($array, $key, $default = null)
    {
        return isset($array[$key])
            ? $array[$key]
            : $default;
    }

    /**
     * Turn's something like PAYONE_DBT into PayoneDbt
     *
     * @param string $paymentMethod - PAYONE_DBT
     *
     * @return string
     */
    public static function paymentMethodToCamelCase($paymentMethod)
    {
        $exploded = explode('_', strtolower($paymentMethod));
        $map      = array_map('ucfirst', $exploded);

        return implode('', $map);
    }
}
