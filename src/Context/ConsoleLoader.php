<?php
/**
 * Copyright (c) Since 2020 Friends of Presta
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file docs/licenses/LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to infos@friendsofpresta.org so we can send you a copy immediately.
 *
 * @author    Friends of Presta <infos@friendsofpresta.org>
 * @copyright since 2020 Friends of Presta
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License ("AFL") v. 3.0
 *
 */

namespace FOP\Console\Context;

use FOP\Console\Controllers\ConsoleController;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;

/**
 * PrestaShop Context in Console Application
 */
final class ConsoleLoader
{
    private $legacyContext;
    private $shopContext;

    public function __construct(LegacyContext $legacyContext, ShopContext $shopContext)
    {
        $this->legacyContext = $legacyContext;
        $this->shopContext = $shopContext;
        require_once _PS_ROOT_DIR_ . '/config/config.inc.php';
    }

    public function loadConsoleContext(InputInterface $input)
    {
        if (!defined('_PS_ADMIN_DIR_')) {
            define('_PS_ADMIN_DIR_', _PS_ROOT_DIR_);
        }
        $employeeId = $input->getOption('employee');
        $shopId = $input->hasOption('id_shop') ? $input->getOption('id_shop') : null;
        $shopGroupId = $input->hasOption('id_shop_group') ? $input->getOption('id_shop_group') : null;
        if ($shopId && $shopGroupId) {
            throw new LogicException('Do not specify an ID shop and an ID group shop at the same time.');
        }
        $this->legacyContext->getContext()->controller = new ConsoleController();
        if (!$this->legacyContext->getContext()->employee) {
            $this->legacyContext->getContext()->employee = new \Employee((int) $employeeId);
        }
        if ($shopId === null) {
            $shopId = 1;
        }
        $shop = $this->legacyContext->getContext()->shop;
        $shop::setContext($shop::CONTEXT_SHOP, (int) $shopId);
        $this->shopContext->setShopContext($shopId);
        $this->legacyContext->getContext()->shop = $shop;
        if ($shopGroupId !== null) {
            $this->shopContext->setShopGroupContext($shopGroupId);
        }
        $this->legacyContext->getContext()->currency = new \Currency((int) \Configuration::get('PS_CURRENCY_DEFAULT') ?: null);
    }
}
