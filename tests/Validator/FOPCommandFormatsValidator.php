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

class FOPCommandFormatsValidator
{
    /**
     * @var string Regular expression for command's fully qualified class name
     */
    private const FQCNRegexp = '#^FOP\\\Console\\\Commands\\\(?<domain>\w+)\\\(?<action>\w+)$#X';
    // @todo other formats should be here.

    /**
     * @var ValidationResults
     */
    private $results;

    /**
     * @param string $commandFQCN php class name, e.g. ModuleHooks
     * @param string $commandName symfony command name, e.g. fop:modules:hooks
     * @param string $commandServiceName service name defined in config/services.yml. e.g. fop.console.modules.module_hooks.command
     *
     * @return ValidationResults
     */
    public function validate(
        string $commandFQCN,
        string $commandName,
        string $commandServiceName
    ): ValidationResults {
        $this->results = new ValidationResults();
        $commandAction = $this->extractActionFromFQCN($commandFQCN);
        $commandDomain = $this->extractDomainFromFQCN($commandFQCN);

        if (empty($commandDomain)) {
            $this->results->addResult(new ValidationResult(false, "Domain can't be empty."));
        }

        if (empty($commandDomain) || strpos($commandAction, $commandDomain) !== 0) {
            $this->results->addResult(new ValidationResult(false, "Domain '$commandDomain' must be included in command class name."));
        }

        $commandAction = str_replace($commandDomain, '', $commandAction);

        if (empty($commandAction)) {
            $this->results->addResult(new ValidationResult(false, "Action can't be empty."));
        }

        $commandDomain = ucfirst($commandDomain);
        $commandAction = ucfirst($commandAction);

        $this->isCommandNameValid($commandAction, $commandName, $commandDomain, $commandAction);

        $this->isCommandServiceNameValid($commandAction, $commandServiceName, $commandDomain, $commandAction);

        if (empty(iterator_to_array($this->results))) {
            $this->results->addResult(new ValidationResult(true, 'Everything checked successfuly'));
        }

        return $this->results;
    }

    private function isCommandNameValid(string $commandClassName, string $commandName, string $commandDomain, string $commandAction): bool
    {
        // Command name pattern = fop:command-domain:command[:-]action
        $expectedCommandNamePattern = strtolower(
            'fop:'
            . implode('-', $this->getWords($commandDomain))
            . ':'
            . implode('[:-]', $this->getWords($commandAction))
        );

        if (!preg_match('/^' . $expectedCommandNamePattern . '$/', $commandName)) {
            $this->results->addResult(new ValidationResult(false, 'Wrong format for command class name.' . PHP_EOL
                . "Expected = $expectedCommandNamePattern" . PHP_EOL
                . "Actual = $commandName"));

            return false;
        }

        return true;
    }

    private function isCommandServiceNameValid(string $commandClassName, string $commandServiceName, string $commandDomain, string $commandAction): bool
    {
        // Command service name pattern = fop.console.command_domain.command[\._]action.command
        $expectedCommandServiceNamePattern = strtolower(
            'fop.console.'
            . implode('_', $this->getWords($commandDomain))
            . '.'
            . implode('[\._]', $this->getWords($commandAction))
            . '.command'
        );

        if (!preg_match('/^' . $expectedCommandServiceNamePattern . '$/', $commandServiceName)) {
            $this->results->addResult(new ValidationResult(false, "Domain can't be empty."));

            return false;
        }

        return true;
    }

    /**
     * @param string $subject
     *
     * @return array<string>
     */
    private function getWords(string $subject): array
    {
        return preg_split('/(?=[A-Z])/', $subject, -1, PREG_SPLIT_NO_EMPTY) ?: [''];
    }

    private function extractDomainFromFQCN(string $fullyQualifiedClassName): string
    {
        return $this->getFQCNRegexpMatches($fullyQualifiedClassName)['domain'] ?? '';
    }

    private function extractActionFromFQCN(string $fullyQualifiedClassName): string
    {
        return $this->getFQCNRegexpMatches($fullyQualifiedClassName)['action'] ?? '';
    }

    /**
     * @param string $fullyQualifiedClassName
     *
     * @return array{domain?: string, action?: string}
     */
    private function getFQCNRegexpMatches(string $fullyQualifiedClassName): array
    {
        preg_match(self::FQCNRegexp, $fullyQualifiedClassName, $matches);

        return $matches ?? [];
    }
}
