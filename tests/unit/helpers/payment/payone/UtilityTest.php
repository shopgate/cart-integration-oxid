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

namespace unit\helpers\payment\payone;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class UtilityTest extends TestCase
{
    /** @var \ShopgatePaymentHelperPayoneUtility */
    private $subjectUnderTest;

    public function set_up()
    {
        $this->subjectUnderTest = $this->getMockBuilder('ShopgatePaymentHelperPayoneUtility')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
    }

    /**
     * @param string $expectedExpiryDate
     * @param string $year
     * @param string $month
     *
     * @dataProvider provideDatesForExpiryDateGeneration
     */
    public function testGeneratePayoneCreditCardExpiryDate($expectedExpiryDate, $year, $month)
    {
        $payonePaymentInfos = $this->generateShopgatePayonePaymentInfos(
            array(
                'credit_card' => array(
                    'expiry_year'  => $year,
                    'expiry_month' => $month,
                ),
            )
        );
        $generateExpiryDate = $this->subjectUnderTest->generatePayoneCreditCardExpiryDate($payonePaymentInfos);

        $this->assertEquals($expectedExpiryDate, $generateExpiryDate);
    }

    /**
     * @return array
     */
    public function provideDatesForExpiryDateGeneration()
    {
        return array(
            'standard case'       => array('1612', '2016', '12',),
            'month only one char' => array('1601', '2016', '1',),
            'empty month'         => array('', '2016', '',),
            'month is null'       => array('', '2016', null,),
            'empty year'          => array('', '', '1',),
            'year is null'        => array('', null, '1',),
        );
    }

    /**
     * @param array $paymentInfos
     *
     * @return \ShopgatePayonePaymentInfos
     */
    private function generateShopgatePayonePaymentInfos(array $paymentInfos)
    {
        return new \ShopgatePayonePaymentInfos($paymentInfos, 2);
    }
}
