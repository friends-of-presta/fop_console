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

class PhpStanNamesConsistencyService
{
    /**
     * @var string Regular expression for command's fully qualified class name
     *
     * @todo this should be in module's main file, fop_console.php
     */
    private const fqcnRegexp = '#^FOP\\\Console\\\Commands\\\(?<domain>\w+)\\\(?<action>\w+)$#X';

    /** @var string */
    private $yamlServicesFilePath;

    /** @var \FOP\Console\Tests\Validator\FOPCommandFormatsValidator */
    private $validator;

    public function __construct(string $yamlServicesFilePath, FOPCommandFormatsValidator $validator)
    {
        $this->yamlServicesFilePath = $yamlServicesFilePath;
        $this->validator = $validator;
    }

    public function validateNames(string $fullyQualifiedClassName, string $command): bool
    {
        return $this->validator->validate(
            $this->extractDomainFromFQCN($fullyQualifiedClassName),
            $this->extractActionFromFQCN($fullyQualifiedClassName),
            $command,
            $this->getServiceNameForFQCN($fullyQualifiedClassName));
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->validator->getValidationMessages();
    }

    private function getServiceNameForFQCN(string $fullyQualifiedClassName): string
    {
        return 'implement me';
    }

    /**
     * @todo this should be in FOP\Console\Tests\Validator\FOPCommandFormatsValidator
     */
    private function extractDomainFromFQCN(string $fullyQualifiedClassName): string
    {
        return $this->getFQCNRegexpMatches($fullyQualifiedClassName)['domain'];
    }

    /**
     * @todo this should be in FOP\Console\Tests\Validator\FOPCommandFormatsValidator
     */
    private function extractActionFromFQCN(string $fullyQualifiedClassName): string
    {
        return $this->getFQCNRegexpMatches($fullyQualifiedClassName)['action'];
    }

    /**
     * @param string $fullyQualifiedClassName
     *
     * @return array{domain: string, action: string}
     */
    private function getFQCNRegexpMatches(string $fullyQualifiedClassName): array
    {
        preg_match(self::fqcnRegexp, $fullyQualifiedClassName, $matches);
        $dummyCapture = 'not extracted from regexp.';

        return array_merge(['domain' => $dummyCapture, 'action' => $dummyCapture], $matches ?? []);
    }
}
