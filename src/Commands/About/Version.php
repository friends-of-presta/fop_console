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
use GuzzleHttp\Client;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepositoryInterface;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class Version extends Command
{
    const GITHUB_RELEASES_YAML_URL = 'https://api.github.com/repos/friends-of-presta/fop_console/releases/latest';

    /** @var \PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepository */
    private $moduleRepository;

    /** @var \Symfony\Component\Console\Style\SymfonyStyle */
    private $io;

    protected function configure(): void
    {
        $this->setName('fop:about:version')
            ->setDescription('Prints the version of this module FoP Console');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        try {
            parent::initialize($input, $output);
            $this->io = new SymfonyStyle($input, $output);

            // get module's information from the Core, not the Adapter, not the legacy, this is the correct way.
            $this->moduleRepository = $this->getContainer()->get('prestashop.core.admin.module.repository');
            if (!$this->moduleRepository instanceof ModuleRepositoryInterface) {
                throw new RuntimeException('Failed to get the ModuleRepository prestashop.core.admin.module.repository');
            }
        } catch (Exception $exception) {
            $this->io->isVerbose()
                ? $this->getApplication()->renderException($exception, $output)
                : $output->write("<error> >>> Error on initialization : {$exception->getMessage()}</error> .");

            exit(1);
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
            // file_get_contents() fails with a 403 error.
            $HttpClient = new Client();
            $response = $HttpClient->get(self::GITHUB_RELEASES_YAML_URL);
            if ($response->getReasonPhrase() !== 'OK') {
                throw new \Exception('Not a 200 Response.');
            }

            return $response->json()['tag_name'];
        } catch (\Exception $exception) {
            if ($this->io->isVerbose()) {
                if (isset($response)) {
                    dump($response->getReasonPhrase());
                    dump($response->getHeaders());
                    dump($response->getBody()->getContents());
                }
                $this->io->error($exception->getMessage());
            }

            return 'Failed to retrieve version on GitHub (use -v for details)';
        }
    }
}
