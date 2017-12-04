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

class Shopgate_Model_Export_Review extends Shopgate_Model_Catalog_Review
{
    /** @var oxReview */
    protected $item;

    /** @var string */
    protected $uniqueArticleIdField;

    /**
     * set unique identifier for articles
     *
     * @param string $value
     */
    public function setUniqueArticleIdentifier($value)
    {
        $this->uniqueArticleIdField = $value;
    }

    public function setUid()
    {
        parent::setUid($this->item->oxreviews__oxid->value);
    }

    public function setItemUid()
    {
        $oxId = $this->item->oxreviews__oxobjectid->value;

        $sql        = "SELECT OXPARENTID FROM oxarticles WHERE oxid = '{$oxId}'";
        $oxParentId = marm_shopgate::dbGetOne($sql);
        if (!empty($oxParentId)) {
            $oxId = $oxParentId;
        }

        if ($this->uniqueArticleIdField == 'oxid') {
            parent::setItemUid($oxId);

            return;
        }
        $sql = "SELECT $this->uniqueArticleIdField FROM oxarticles WHERE oxid = '{$oxId}'";
        $id  = marm_shopgate::dbGetOne($sql);
        parent::setItemUid($id);
    }

    public function setScore()
    {
        parent::setScore($this->item->oxreviews__oxrating->value * 2);
    }

    public function setReviewerName()
    {
        $reviewerName = '(anonym)';
        if (!empty($this->item->oxuser__oxfname->value)) {
            $reviewerName = $this->item->oxuser__oxfname->value;
        }
        parent::setReviewerName($reviewerName);
    }

    public function setDate()
    {
        parent::setDate(date('Y-m-d', strtotime($this->item->oxreviews__oxcreate->value)));
    }

    public function setTitle()
    {
        parent::setTitle('');
    }

    public function setText()
    {
        parent::setText($this->item->oxreviews__oxtext->value);
    }

    /**
     * @return array
     */
    public function asCsv()
    {
        return array(
            'update_review_id' => $this->getUid(),
            'item_number'      => $this->getItemUid(),
            'score'            => $this->getScore(),
            'name'             => $this->getReviewerName(),
            'date'             => $this->getDate(),
            'title'            => $this->getTitle(),
            'text'             => $this->getText(),
        );
    }
}
