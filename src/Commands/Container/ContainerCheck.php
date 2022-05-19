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

namespace FOP\Console\Commands\Container;

use Exception;
use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Throwable;

/**
 * Check health of our Service Container.
 */
final class ContainerCheck extends Command
{
    private static $containerBuilder;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:container:check')
            ->setAliases(['fop:check-container'])
            ->setDescription('Health check of the Service Container')
            ->setHelp('This command instantiate every service of Symfony in Console Context: will it works as expected ?')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Service Container Health Checker');
        $services = [];
        $errors = 0;

        foreach ($this->getContainerBuilder()->getServiceIds() as $serviceId) {
            if (
                $this->getContainerBuilder()->has($serviceId) &&
                !array_key_exists($serviceId, $this->getContainerBuilder()->getRemovedIds()) &&
                !array_key_exists($serviceId, $this->getContainerBuilder()->getAliases()) &&
                $this->getContainerBuilder()->getDefinition($serviceId)->isPublic() &&
                !$this->getContainerBuilder()->getDefinition($serviceId)->isAbstract()
            ) {
                try {
                    $this->getContainer()->get($serviceId);
                } catch (Exception $exception) {
                    $services[] = [$serviceId, '<fg=red>' . $this->formatException($exception) . '</>'];
                } catch (Throwable $error) {
                    $services[] = [$serviceId, '<fg=red>' . $this->formatException($error) . '</>'];
                    ++$errors;
                }
            }
        }

        if ($errors > 0) {
            $this->io->table(
                ['ID', 'Error message'],
                $services
            );

            return 255;
        }

        $this->io->success('The Service Container is valid.');

        return 0;
    }

    private function formatException($exception): string
    {
        /* @var Exception $exception */
        return $exception->getMessage() . PHP_EOL . ' at ' . $exception->getFile() . ':' . $exception->getLine();
    }

    /**
     * Loads the ContainerBuilder from the cache.
     *
     * @return ContainerBuilder
     *
     * @throws \LogicException
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        if (null !== self::$containerBuilder) {
            return self::$containerBuilder;
        }

        $kernel = $this->getApplication()->getKernel();

        $buildContainer = \Closure::bind(function () {
            return $this->buildContainer();
        }, $kernel, \get_class($kernel));
        $container = $buildContainer();
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();
        self::$containerBuilder = $container;

        return self::$containerBuilder;
    }
}
