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
use OxidEsales\Eshop\Application\Model\Article as oxArticle;

/**
 * Admin controller for Shopgate tab in article list, so Shopgate values
 * can be set and edited.
 */
class Article extends Article_parent
{
    protected $_sThisTemplate = 'marm_shopgate_article.tpl';

    /**
     * stores active article for editing
     *
     * @var oxArticle
     */
    protected $_oArticle = null;

    /**
     * returns active article for editing
     *
     * @param bool $blReset
     *
     * @return oxArticle
     */
    public function getArticle($blReset = false)
    {
        if ($this->_oArticle !== null && !$blReset) {
            return $this->_oArticle;
        }

        $soxId = $this->getEditObjectId();
        $this->_oArticle = oxNew(oxArticle::class);
        $this->_oArticle->load($soxId);

        return $this->_oArticle;
    }

    /**
     * Saves changes of article parameters.
     */
    public function save()
    {
        $soxId = $this->getEditObjectId();
        $aParams = Module::getRequestParameter('editval');

        /** @var oxArticle $oArticle */
        $oArticle = oxNew(oxArticle::class);
        $oArticle->setLanguage($this->_iEditLang);
        $oArticle->loadInLang($this->_iEditLang, $soxId);
        $oArticle->setLanguage(0);

        $oArticle->assign($aParams);
        $oArticle->setLanguage($this->_iEditLang);
        $oArticle->save();

        $this->setEditObjectId($oArticle->getId());
    }
}
