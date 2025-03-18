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

namespace Shopgate\Oxid\Controller\Admin;

use Shopgate\Oxid\Core\Module;

/**
 * Admin controller for Shopgate config tab
 */
class Config extends Config_Parent
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
     * @param bool $blReset
     *
     * @return array
     * @see Module::getConfigForAdminGui()
     *
     */
    public function getShopgateConfig($blReset = false)
    {
        if ($this->_aShopgateConfig === null || $blReset) {
            $this->_aShopgateConfig = Module::getInstance()->getConfigForAdminGui();
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
