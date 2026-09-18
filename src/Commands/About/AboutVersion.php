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

declare(strict_types=1);

namespace FOP\Console\Commands\About;

use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AboutVersion extends Command
{
    public const GITHUB_RELEASES_YAML_URL = 'https://api.github.com/repos/friends-of-presta/fop_console/releases/latest';

    /**
     * @var mixed
     *            PrestaShop\PrestaShop\Core\Module\ModuleRepositoryInterface or PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepositoryInterface
     *            (same interface but moved for v8 version)
     */
    private $moduleRepository;

    protected function configure(): void
    {
        $this->setName('fop:about:version')
            ->setDescription('Prints the version of this module FoP Console');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        // get module's information from the Core, not the Adapter, not the legacy, this is the correct way.
        $isPs9 = version_compare(_PS_VERSION_, '9.0.0', '>=');
        if ($isPs9) {
            $this->getContainer()
                ->get('PrestaShop\\PrestaShop\\Core\\Context\\ContextBuilderPreparer')
                ->prepareLanguageId((int) \Configuration::get('PS_LANG_DEFAULT'));
        }

        $repositoryService = $isPs9
            ? 'PrestaShop\\PrestaShop\\Core\\Module\\ModuleRepository'
            : 'prestashop.core.admin.module.repository';
        $this->moduleRepository = $this->getContainer()->get($repositoryService);

        // For v8, `ModuleRepositoryInterface` has been moved.
        $isPsBeforeV8 = version_compare(_PS_VERSION_, '8.0.0', '<');
        $isModuleRepositoryExpectedType = $isPsBeforeV8 ?
            /* @phpstan-ignore-next-line */
            ($this->moduleRepository instanceof \PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepositoryInterface) : ($this->moduleRepository instanceof \PrestaShop\PrestaShop\Core\Module\ModuleRepositoryInterface);

        if (!$isModuleRepositoryExpectedType) {
            throw new \RuntimeException('Failed to get the ModuleRepository prestashop.core.admin.module.repository');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fopModule = $this->moduleRepository->getModule('fop_console');
        $properties = [
            'registered version' => $fopModule->get('version'),
            'disk version' => $fopModule->get('version_available'),
            'last release' => $this->getLastReleaseVersion(),
        ];

        $this->io->table(array_keys($properties), [$properties]);

        return 0;
    }

    private function getLastReleaseVersion(): string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'header' => "Accept: application/vnd.github+json\r\nUser-Agent: friends-of-presta-fop-console\r\n",
                    'timeout' => 5,
                ],
            ]);
            $response = @file_get_contents(self::GITHUB_RELEASES_YAML_URL, false, $context);
            if ($response === false) {
                throw new \RuntimeException('Unable to retrieve the latest release from GitHub.');
            }

            $release = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return (string) $release['tag_name'];
        } catch (\Exception $exception) {
            if ($this->io->isVerbose()) {
                $this->io->error($exception->getMessage());
            }

            return 'Failed to retrieve version on GitHub (use -v for details)';
        }
    }
}
