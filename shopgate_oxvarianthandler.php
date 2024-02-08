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
 * Overload the oxvarianthandler in oxid to
 * set the shopgate-fields to its default-value
 *
 * marm_shopgate_marketplace => 1
 * marm_shopgate_export => 1
 */
class shopgate_oxvarianthandler extends shopgate_oxvarianthandler_parent
{
    protected function _createNewVariant($aParams = null, $sParentId = null)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $oxid = parent::_createNewVariant($aParams, $sParentId);

        $this->sg_setDefaultValues($oxid);

        return $oxid;
    }

    //before Oxid 4.5.0 this function was written with a typo
    protected function _craeteNewVariant($aParams = null, $sParentId = null)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $oxid = parent::_craeteNewVariant($aParams, $sParentId);

        $this->sg_setDefaultValues($oxid);

        return $oxid;
    }

    private function sg_setDefaultValues($oxid)
    {
        /** @var oxArticle $oArticle */
        $oArticle = oxNew('oxarticle');
        $oArticle->load($oxid);

        $oArticle->oxarticles__marm_shopgate_marketplace = new oxField('1', oxField::T_RAW);
        $oArticle->oxarticles__marm_shopgate_export      = new oxField('1', oxField::T_RAW);
        $oArticle->save();
    }
}
