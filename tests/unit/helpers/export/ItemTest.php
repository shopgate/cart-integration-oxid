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

namespace unit\helpers\export;

class ItemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * See OXID-339 for more information
     */
    public function testTierPricesRoundingError()
    {
        $oxArticleMock = $this->getOxArticleMock(array('loadAmountPriceInfo'));
        $sut           = $this->getShopgateItemExportHelper(
            array('getUnitAmountNet', 'getVpe', 'getPriceForQuantityAndGroup',)
        );

        $sut->expects($this->any())->method('getUnitAmountNet')->will($this->returnValue(3.3193));
        $sut->expects($this->any())->method('getPriceForQuantityAndGroup')->will($this->returnValue(3.319327731));

        $oxArticleMock->expects($this->any())->method('loadAmountPriceInfo')->will($this->returnValue(array()));

        $tierPrices = $sut->getTierPrices($oxArticleMock);

        $this->assertEquals(true, empty($tierPrices));
    }

    public function testCustomerGroupPrice()
    {
        $sut = $this->getShopgateItemExportHelper(array('getUnitAmountNet', 'getVpe', 'getPriceForQuantityAndGroup',));

        $sut->expects($this->any())->method('getUnitAmountNet')->will($this->returnValue(3.3193));
        $sut->expects($this->any())->method('getPriceForQuantityAndGroup')->will($this->returnValue(2.50));

        $oxArticleMock = $this->getOxArticleMock(array('loadAmountPriceInfo'));
        $oxArticleMock->expects($this->any())->method('loadAmountPriceInfo')->will($this->returnValue(array()));

        $tierPrices = $sut->getTierPrices($oxArticleMock);

        $this->assertEquals(true, is_array($tierPrices));

        foreach ($tierPrices as $tierPrice) {
            $this->assertEquals('0.8193', $tierPrice->getReduction());
            $this->assertEquals('fixed', $tierPrice->getReductionType());
        }
    }

    public function testWeightUnitNotEmpty()
    {
        $sut = $this->getShopgateItemExportHelper(array('__construct'));

        $oxArticle = $this->getOxArticleMock(array('__construct'));

        $this->assertNotEmpty($sut->getWeightUnit($oxArticle));
    }

    /**
     * @param array $methods
     *
     * @return \oxArticle|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getOxArticleMock(array $methods = array())
    {
        return $this->getMockBuilder('oxArticle')
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     *
     * @return \ShopgateItemExportHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getShopgateItemExportHelper(array $methods = array())
    {
        return $this->getMockBuilder('ShopgateItemExportHelper')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}
