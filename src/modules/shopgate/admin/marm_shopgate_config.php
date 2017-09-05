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

if (version_compare(marm_shopgate::getOxConfig()->getVersion(), '4.2.0', "<")) {

    //In Oxid < 4.2.0 the smarty tag "oxinputhelp" does not exist. So we create our own version...

    function oxinputhelpTag($params)
    {
        return marm_shopgate::oxinputhelpTag($params);
    }

    marm_shopgate::getOxUtilsView()->getSmarty()->register_function('oxinputhelp', 'oxinputhelpTag');
}

/**
 * Admin controller for Shopgate config tab
 */
class marm_shopgate_config extends Shop_Config
{
    /**
     * shopgate configuration template
     *
     * @var string
     */
    protected $_sThisTemplate = 'marm_shopgate_config.tpl';

    /**
     * stores array for shopgate config, with information how to display it
     *
     * @var array
     */
    protected $_aShopgateConfig = null;

    /**
     * returns shopgate config array with information how to display it
     *
     * @see marm_shopgate::getConfigForAdminGui()
     *
     * @param bool $blReset
     *
     * @return array
     */
    public function getShopgateConfig($blReset = false)
    {
        if ($this->_aShopgateConfig === null || $blReset) {
            $this->_aShopgateConfig = marm_shopgate::getInstance()->getConfigForAdminGui();
        }

        return $this->_aShopgateConfig;
    }

    public function getPluginVersion($blReset = false)
    {
        return SHOPGATE_PLUGIN_VERSION;
    }

    public function getLibraryVersion($blReset = false)
    {
        return SHOPGATE_LIBRARY_VERSION;
    }
}
