<?php

namespace FOP\Console\Context;

use Currency;
use Employee;
use FOP\Console\Controllers\ConsoleController;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Exception\LogicException;
use PrestaShop\PrestaShop\Adapter\Shop\Context as ShopContext;
use PrestaShop\PrestaShop\Core\Console\ContextLoaderInterface;

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
    /**
     * {@inheritdoc}
     */
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
