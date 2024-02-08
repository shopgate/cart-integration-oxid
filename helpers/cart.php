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
 * Helper class used by check_cart
 */
class ShopgateCartHelper
{
    /** @var ShopgateConfigOxid */
    private $config;

    /** @var oxUser */
    private $oxUser;

    /**
     * @param ShopgateConfigOxid $config
     */
    public function __construct(ShopgateConfigOxid $config)
    {
        $this->config = $config;
    }

    /**
     * @param oxUser $oxUser
     *
     * @return ShopgateCartCustomer
     */
    public function buildCartCustomer(oxUser $oxUser)
    {
        $this->oxUser = $oxUser;

        $customer = new ShopgateCartCustomer();
        $customer->setCustomerGroups($this->getCustomerGroups());

        return $customer->utf8Encode($this->config->getEncoding());
    }

    /**
     * @return ShopgateCartCustomerGroup[]
     */
    private function getCustomerGroups()
    {
        $customerGroups = array();
        foreach (marm_shopgate::getUserGroupsByUser($this->oxUser) as $oxGroup) {
            $group = new ShopgateCartCustomerGroup();
            $group->setId($oxGroup->oxgroups__oxid->value);
            $customerGroups[$oxGroup->oxgroups__oxid->value] = $group;
        }

        return $customerGroups;
    }

    /**
     * In case method getItems on the cart object returns not an array this method will return true
     *
     * @param ShopgateCart $cart
     *
     * @return bool
     */
    public function isShoppingCartEmpty(ShopgateCart $cart)
    {
        $cartItems = $cart->getItems();

        if (!is_array($cartItems) || count($cartItems) == 0) {
            return true;
        }

        return false;
    }
}
