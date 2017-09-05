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

class shopgate_actions extends oxAdminDetails
{
    protected $_sThisTemplate = 'shopgate_actions.tpl';

    public function render()
    {
        $return = parent::render();

        $sOXID = marm_shopgate::getRequestParameter('oxid');
        /** @var oxActions $oAction */
        $oAction = oxNew('oxactions');
        $oAction->load($sOXID);
        $this->_aViewData['is_highlight'] = $oAction->oxactions__shopgate_is_highlight->value;

        return $return;
    }

    public function save()
    {
        $sOXID = marm_shopgate::getRequestParameter('oxid');

        $isHighlight = marm_shopgate::getRequestParameter('is_highlight');
        $isHighlight = $isHighlight === 'on';

        /** @var oxActions $oAction */
        $oAction = oxNew('oxactions');
        $oAction->load($sOXID);

        $oAction->oxactions__shopgate_is_highlight = new oxField($isHighlight, oxField::T_RAW);

        $oAction->save();
    }
}
