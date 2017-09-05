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

require_once dirname(__FILE__) . '/../src/modules/shopgate/vendor/autoload.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/shopgate_config_oxid.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/helpers/cart.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/helpers/config/unknown_oxid_config_fields.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/helpers/export/customer.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/helpers/export/item.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/helpers/export/settings.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/helpers/payment/base.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/helpers/payment/payone/utility.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/helpers/payment/models/payone_payment_infos.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/model/export/item.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/model/export/category.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/model/export/review.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/helpers/shopgate_order_export_helper.php';
require_once dirname(__FILE__) . '/../src/modules/shopgate/metadata.php';
