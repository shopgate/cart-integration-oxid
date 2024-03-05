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

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class UnknownOxidConfigFieldsTest extends TestCase
{
    /** @var \ShopgateUnknownOxidConfigFields */
    private $subjectUnderTest;

    /** @var \oxConfig|\PHPUnit_Framework_MockObject_MockObject */
    private $oxidConfigMock;

    /** @var \ShopgateConfigOxid|\PHPUnit_Framework_MockObject_MockObject */
    private $shopgateConfigMock;

    /** @var \marm_shopgate|\PHPUnit_Framework_MockObject_MockObject */
    private $marmShopgateMock;

    public function set_up()
    {
        $this->oxidConfigMock = $this->getMockBuilder('\oxConfig')
            ->setMethods(array('saveShopConfVar', 'getConfigParam'))
            ->getMock();

        $this->shopgateConfigMock = $this->getMockBuilder('\ShopgateConfigOxid')
            ->disableOriginalConstructor()
            ->getMock();

        $this->marmShopgateMock = $this->getMockBuilder('marm_shopgate')
            ->setMethods(array('getOxidConfigKey'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->subjectUnderTest =
            new \ShopgateUnknownOxidConfigFields(
                $this->shopgateConfigMock,
                $this->oxidConfigMock,
                $this->marmShopgateMock
            );
    }

    public function testRunSaveMethod()
    {
        $this->subjectUnderTest->save(array('html_tags' => 'test'));
    }

    public function testLoadConfig()
    {
        $this->oxidConfigMock->method('getConfigParam')->willReturn('{"html_tags" : "test"}');

        $this->shopgateConfigMock->expects($this->once())->method('jsonDecode')->with(
            '{"html_tags" : "test"}',
            true
        )->willReturn(
            array('html_tags' => 'test')
        );
        $this->shopgateConfigMock->expects($this->once())->method('camelize')->with('html_tags')->willReturn(
            'HtmlTags'
        );

        $this->shopgateConfigMock->expects($this->once())->method('setHtmlTags')->with('test');

        $this->subjectUnderTest->load();
    }

    /**
     * There is already a configuration value for 'html_tags' and 'encoding' which gets loaded.
     * Two new configuration values should be saved 'html_tags' and 'shop_is_active'.
     * We need to check the new value for html_tags, see that encoding is still there
     */
    public function testRunSaveMethodAfterLoad()
    {
        $this->oxidConfigMock->method('getConfigParam')->willReturn('{"html_tags" : "test", "encoding" : "UTF-8"}');

        $this->shopgateConfigMock->expects($this->once())->method('jsonDecode')->with(
            '{"html_tags" : "test", "encoding" : "UTF-8"}',
            true
        )
            ->willReturn(array('html_tags' => 'test', 'encoding' => 'UTF-8'));
        $this->shopgateConfigMock->method('camelize')->willReturnOnConsecutiveCalls('HtmlTags', 'Encoding', 'Encoding');

        $this->shopgateConfigMock->expects($this->once())->method('setHtmlTags')->with('test');
        $this->shopgateConfigMock->expects($this->once())->method('setEncoding')->with('UTF-8');
        $this->shopgateConfigMock->expects($this->once())->method('getEncoding')->willReturn('UTF-8');

        $this->subjectUnderTest->load();

        $this->shopgateConfigMock->expects($this->once())->method('jsonEncode')->with(
            array(
                'html_tags' => '{"html_tags" : "test"}',
                'encoding' => 'UTF-8',
                'shop_is_active' => 1,
            )
        );

        $this->subjectUnderTest->save(
            array(
                'shop_is_active' => 1,
                'html_tags'      => '{"html_tags" : "test"}',
            )
        );
    }

    public function testLoadConfigNotExistingFields()
    {
        $this->oxidConfigMock->method('getConfigParam')->willReturn('{"abc123" : "test"}');

        $this->shopgateConfigMock->expects($this->once())->method('jsonDecode')->with(
            '{"abc123" : "test"}',
            true
        )->willReturn(
            array('abc123' => 'test')
        );
        $this->shopgateConfigMock->expects($this->once())->method('camelize')->with('abc123')->willReturn('Abc123');

        $this->subjectUnderTest->load();
    }
}
