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

use OxidEsales\Eshop\Application\Controller\Admin\ActionsController;
use OxidEsales\Eshop\Application\Controller\Admin\ArticleController;
use OxidEsales\Eshop\Application\Controller\Admin\DeliverySetMain;
use OxidEsales\Eshop\Application\Controller\Admin\OrderMain;
use OxidEsales\Eshop\Application\Controller\Admin\OrderOverview;
use OxidEsales\Eshop\Application\Controller\Admin\PaymentMain;
use OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration;

class Actions_parent extends ActionsController
{
}

class Article_parent extends ArticleController
{
}

class Config_parent extends ShopConfiguration
{
}

class Order_parent extends OrderMain
{
}

class OrderOverview_parent extends OrderOverview
{
}

class Payment_parent extends PaymentMain
{
}

class Shipping_parent extends DeliverySetMain
{
}
