<?php

namespace FOP\Commands;

use Exception;
use Throwable;
use PrestaShop\PrestaShop\Core\Console\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PrestaShopBundle\Exception\NotImplementedException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This command is an health check of our Service Container.
 */
final class CheckServiceContainer extends Command
{
    private static $containerBuilder;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('fop:commands:check-container')
            ->setDescription('Health check of the Service Container')
            ->setHelp('This command instantiate every service of Symfony in Console Context: will it works as expected ?')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Service Container Health Checker');
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
                } catch (NotImplementedException $notImplementedException) {
                    $services[] =  [$serviceId, '<comment>' . $this->formatException($notImplementedException) . '</comment>'];
                } catch (Exception $exception) {
                    $services[] =  [$serviceId, '<fg=red>' . $this->formatException($exception) . '</>'];
                } catch (Throwable $error) {
                    $services[] =  [$serviceId, '<fg=red>' . $this->formatException($error) . '</>'];
                    $errors++;
                }
            }
        }

        if ($errors > 0) {
            $io->table(
                ['ID', 'Error message'],
                $services
            );

            return 255;
        }

        $io->success('The Service Container is valid.');

        return 0;
    }

    private function formatException($exception)
    {
        /** @var Exception $exception */
        return $exception->getMessage(). PHP_EOL . ' at ' . $exception->getFile() . ':' . $exception->getLine();
    }

    /**
     * Loads the ContainerBuilder from the cache.
     *
     * @return ContainerBuilder
     *
     * @throws \LogicException
     */
    private function getContainerBuilder()
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
