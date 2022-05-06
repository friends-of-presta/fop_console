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

namespace FOP\Console\Tests\Validator;

use Exception;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class PhpStanNamesConsistencyService
{
    /** @var string */
    private $yamlServicesFilePath;

    /** @var \FOP\Console\Tests\Validator\FOPCommandFormatsValidator */
    private $validator;

    /** @var ?array<string, string> */
    private $servicesNamesCache;

    public function __construct(string $yamlServicesFilePath, FOPCommandFormatsValidator $validator)
    {
        $this->yamlServicesFilePath = $yamlServicesFilePath;
        $this->validator = $validator;
    }

    public function validateNames(string $fullyQualifiedClassName, string $command): ValidationResults
    {
        try {
            return $this->validator->validate(
                $fullyQualifiedClassName,
                $command,
                $this->getServiceNameForFQCN($fullyQualifiedClassName)
            );
        } catch (Exception $exception) {
            throw new RuntimeException(__CLASS__ . ' Internal error : ' . $exception->getMessage());
        }
    }

    private function getServiceNameForFQCN(string $fullyQualifiedClassName): string
    {
        $services = $this->getServicesNames();
        if (!isset($services[$fullyQualifiedClassName])) {
//            dump($fullyQualifiedClassName, $services);
            throw new Exception('Service not found in service.yaml.' . PHP_EOL . 'Maybe unsupported syntax.' . PHP_EOL . 'Use this form :' . PHP_EOL . '' . PHP_EOL . ' fop.console.domain.action.command:' . PHP_EOL . '   class: FOP\\Console\\Commands\\Domain\\DomainAction' . PHP_EOL . '   tags: [ console.command ]');
        }

        return $services[$fullyQualifiedClassName] ?? '';
    }

    /**
     * @return array<string, string> index : className, value : serviceName
     *
     * @todo the direct form of commands declaration are found, not the long form
     * Recognized :
     * ```yaml
     *   fop.console.module.non_essential.command:
     *     class: FOP\Console\Commands\Module\ModuleNonEssential
     *     tags: [ console.command ]
     *```
     * Not recognized :
     * ```yaml
     * tags:
     *  - {name: console.command}
     * ```
     */
    private function getServicesNames(): array
    {
        if (is_null($this->servicesNamesCache)) {
            $yaml = Yaml::parseFile($this->yamlServicesFilePath);
            if (!isset($yaml['services'])) {
                throw new RuntimeException('Unexpected Symfony config file content : "services" section not found.');
            }
            $filterServicesWithConsoleTag = static function (array $service) {
                return isset($service['tags']) && in_array('console.command', $service['tags']); // direct form
            };
            $commands = array_filter($yaml['services'], $filterServicesWithConsoleTag);
            $servicesWithServiceField = array_map(
                static function (string $service, array $classDefinition) {
                    return [
                    'service' => $service,
                    'class' => $classDefinition['class'],
                ];
                },
                array_keys($commands),
                $commands
            );

            $this->servicesNamesCache = array_column($servicesWithServiceField, 'service', 'class');
        }

        return $this->servicesNamesCache;
    }
}
