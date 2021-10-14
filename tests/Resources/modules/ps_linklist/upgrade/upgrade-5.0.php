<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_5_0()
{
    $result = true;
    $result &= Db::getInstance()->execute('ALTER TABLE `' . _DB_PREFIX_ . 'link_block_shop`  ADD COLUMN `position` int(10) unsigned NOT NULL DEFAULT 0');

    foreach (Shop::getContextListShopID() as $shopId) {
        $result &= Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . 'link_block_shop` (`id_link_block`, `position`, `id_shop`)
            SELECT `id_link_block`, `position`, ' . $shopId . ' FROM `' . _DB_PREFIX_ . 'link_block`
            '
        );
    }

    return (bool) $result;
}
