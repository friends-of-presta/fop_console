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

namespace FOP\Console\Commands;

use FOP\Console\Command;
use FOP\Console\Overriders\OverriderInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MakeOverride extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:override')
            ->setDescription('Generate a file to make an override.')
            ->addArgument('path', InputArgument::REQUIRED, 'file to override.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');

        try {
            $overriders = $this->getOverriders();
            $handled = false;
            foreach ($overriders as $overrider) {
                $overrider->run($path, $io);
                $handled = $handled || $overrider->isHandled();
            }

            if ($handled) {
                $io->success('Override(s) done :)');
                return 0;
            }
                // io message ...
                $io->text("No overrider found for file $path");
//                return 2; // @todo another return code ?
                // ... or Exception
                // @todo Any preference ?
                // may depend on verbose level..
//                throw new \Exception('No overrider found.');

            return 0;
        } catch (\Exception $exception) {
            $io->error("Override for '$path' fails : {$exception->getMessage()}");

            return 1; // @todo a better error code to return ? 255 ?
        }
    }

    /**
     * @return OverriderInterface[]
     */
    private function getOverriders(): array
    {
        $override_provider = $this->getContainer()->get('fop.console.overrider_provider');

        return $override_provider->getOverriders();
    }
}
