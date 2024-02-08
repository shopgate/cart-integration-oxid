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
 * this will emulate oxid 4.5.x  oxAdminView::getEditObjectId()
 */
class marm_shopgate_oxadminview extends marm_shopgate_oxadminview_parent
{
    /**
     * Editable object id
     *
     * @var string
     */
    protected $_sEditObjectId = null;

    /**
     * Returns active/editable object id
     *
     * @return string
     */
    public function getEditObjectId()
    {
        if (null === ($sId = $this->_sEditObjectId)) {
            if (null === ($sId = marm_shopgate::getRequestParameter("oxid"))) {
                /** @noinspection PhpUndefinedMethodInspection */
                $sId = marm_shopgate::getSessionVar("saved_oxid");
            }
        }

        return $sId;
    }

    /**
     * Sets editable object id
     *
     * @param string $sId object id
     *
     * @return string
     */
    public function setEditObjectId($sId)
    {
        $this->_sEditObjectId           = $sId;
        $this->_aViewData["updatelist"] = 1;
    }
}
