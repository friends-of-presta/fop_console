<?php
/**
 * 2019-present Friends of Presta community
 * NOTICE OF LICENSE
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author Friends of Presta community
 * @copyright 2019-present Friends of Presta community
 * @license https://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace FOP\Console\Commands\About;

use Exception;
use FOP\Console\Command;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepositoryInterface;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Version extends Command
{
    /** @var \PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepository */
    private $moduleRepository;

    protected function configure(): void
    {
        $this->setName('fop:version')
            ->setDescription('Prints the version of this module FoP Console');
    }

    protected function initialize(InputInterface $input, OutputInterface $output) : void
    {
        try {
            parent::initialize($input, $output);

            // get module's information from the Core, not the Adapter, not the legacy, this is the correct way.
            $this->moduleRepository = $this->getContainer()->get('prestashop.core.admin.module.repository');
            if (!$this->moduleRepository instanceof ModuleRepositoryInterface) {
                throw new RuntimeException('Failed to get the ModuleRepository prestashop.core.admin.module.repository');
            }
        } catch (Exception $exception) {
            $output->write("<fg=red> >>> Error on initialization : {$exception->getMessage()}</fg=red> .");
            exit(1);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $fopModule = $this->moduleRepository->getModule('fop_console');
        $properties = [
            'registered version' => $fopModule->get('version'),
            'disk version' => $fopModule->get('version_available'),
//            'last release' => ?
            ];

        $io = new SymfonyStyle($input, $output);
        $io->table(array_keys($properties), [$properties]);

        return 0;
    }
}
