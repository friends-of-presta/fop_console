<?php
/**
 * 2020-present Friends of Presta community
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author Friends of Presta community
 * @copyright 2020-present Friends of Presta community
 * @license https://opensource.org/licenses/MIT MIT
 */
declare(strict_types=1);

namespace FOP\Console\Commands;

use FOP\Console\Command;
use FOP\Console\Overriders\OverriderInterface;
use FOP\Console\Overriders\Provider;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeOverride extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:override')
            ->setDescription('Generate a file to make an override.')
            ->addArgument('path', InputArgument::REQUIRED, 'file to override.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = (string) $input->getArgument('path');

        try {
            $overriders = $this->getOverriders($path);
            if (empty($overriders)) {
                $io->comment("No Overrider for path '$path' fails");

                return 0;
            }

            $messages = [];
            foreach ($overriders as $overrider) {
                $messages = $overrider->run($path);
            }

            $io->block($messages);

            return 0;
        } catch (\Exception $exception) {
            $io->error("Override for '$path' fails : {$exception->getMessage()}");

            return 1;
        }
    }

    /**
     * @param string $path
     *
     * @return OverriderInterface[]
     */
    private function getOverriders(string $path): array
    {
        /** @var Provider $override_provider */
        $override_provider = $this->getContainer()->get('fop.console.overrider_provider');

        return $override_provider->getOverriders($path);
    }
}
