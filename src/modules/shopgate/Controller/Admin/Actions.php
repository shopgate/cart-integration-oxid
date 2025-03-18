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

use Exception;
use Shopgate\Oxid\Core\Module;
use OxidEsales\Eshop\Application\Model\Actions as oxActions;
use OxidEsales\Eshop\Core\Field;

class Actions extends Actions_parent
{
    protected $_sThisTemplate = 'shopgate_actions.tpl';

    public function render()
    {
        $return = parent::render();

        $sOXID = Module::getRequestParameter('oxid');
        /** @var oxActions $oAction */
        $oAction = oxNew(oxActions::class);
        $oAction->load($sOXID);
        $this->_aViewData['is_highlight'] = $oAction->oxactions__shopgate_is_highlight->value;

        return $return;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function save()
    {
        $sOXID = Module::getRequestParameter('oxid');

        $isHighlight = Module::getRequestParameter('is_highlight');
        $isHighlight = $isHighlight === 'on';

        /** @var oxActions $oAction */
        $oAction = oxNew(oxActions::class);
        $oAction->load($sOXID);
        $oAction->oxactions__shopgate_is_highlight = new Field($isHighlight, Field::T_RAW);

        $oAction->save();
    }
}
