<?php
/**
 * 2019-present Friends of Presta community
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author Friends of Presta community
 * @copyright 2019-present Friends of Presta community
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace FOP\Console\Context;

use Currency;
use Employee;
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
    private $rootDir;

    public function __construct(LegacyContext $legacyContext, ShopContext $shopContext, $rootDir)
    {
        $this->legacyContext = $legacyContext;
        $this->shopContext = $shopContext;
        $this->rootDir = $rootDir;
        require_once $rootDir . '/../config/config.inc.php';
    }

    public function loadConsoleContext(InputInterface $input)
    {
        if (!defined('_PS_ADMIN_DIR_')) {
            define('_PS_ADMIN_DIR_', $this->rootDir);
        }
        $employeeId = $input->getOption('employee');
        $shopId = $input->getOption('id_shop');
        $shopGroupId = $input->getOption('id_shop_group');
        if ($shopId && $shopGroupId) {
            throw new LogicException('Do not specify an ID shop and an ID group shop at the same time.');
        }
        $this->legacyContext->getContext()->controller = new ConsoleController();
        if (!$this->legacyContext->getContext()->employee) {
            $this->legacyContext->getContext()->employee = new Employee($employeeId);
        }
        $shop = $this->legacyContext->getContext()->shop;
        $shop::setContext(1);
        if ($shopId === null) {
            $shopId = 1;
        }
        $this->shopContext->setShopContext($shopId);
        $this->legacyContext->getContext()->shop = $shop;
        if ($shopGroupId !== null) {
            $this->shopContext->setShopGroupContext($shopGroupId);
        }
        $this->legacyContext->getContext()->currency = new Currency();
    }
}
