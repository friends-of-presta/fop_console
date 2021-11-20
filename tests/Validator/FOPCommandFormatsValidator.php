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

/**
 * Class FOPCommandFormatsValidator
 *
 * Rules :
 * - FQCN must follow pattern : FOP\Console\Commands\<Domain>\<Domain><Action>
 *   - <Domain> is not empty
 *   - <Action> is not empty
 * - command name (symfony command name) is consistent with <Domain> and <Action>
 * - service name (symfony service declaration) is consistent with <Domain> and <Action>
 */
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
     * @param string $fullyQualifiedClassName php class name, e.g. ModuleHooks
     * @param string $commandName symfony command name, e.g. fop:modules:hooks
     * @param string $service service name defined in config/services.yml. e.g. fop.console.modules.module_hooks.command
     *
     * @return ValidationResults
     */
    public function validate(
        string $fullyQualifiedClassName,
        string $commandName,
        string $service
    ): ValidationResults {
        $this->results = new ValidationResults();

        $this->checkDomainIsNotEmptyInClassName($fullyQualifiedClassName);
        $this->checkActionIsNotEmptyInClassName($fullyQualifiedClassName);
        $this->checkDomainIsRepeatedInActionInClassName($fullyQualifiedClassName);
        $this->checkCommandNameIsConsistentWithClassName($commandName, $fullyQualifiedClassName);
        $this->checkServiceNameIsConsistentWithClassName($service, $fullyQualifiedClassName);

        if (empty(iterator_to_array($this->results))) {
            $this->results->addResult(new ValidationResult(true, 'Everything checked successfully.'));
        }

        return $this->results;
    }

    private function checkDomainIsNotEmptyInClassName(string $fullyQualifiedClassName): void
    {
        $domain = $this->extractDomainFromFQCN($fullyQualifiedClassName);
        if (empty($domain)) {
            $this->results->addResult(new ValidationResult(false, "Domain can't be empty."));
        }
    }

    private function checkActionIsNotEmptyInClassName(string $fullyQualifiedClassName): void
    {
        $action = $this->extractActionFromFQCN($fullyQualifiedClassName);

        if (empty($action)) {
            $this->results->addResult(new ValidationResult(false, "Action can't be empty."));
        }
    }

    private function checkDomainIsRepeatedInActionInClassName(string $fullyQualifiedClassName): void
    {
        $action = $this->extractActionFromFQCN($fullyQualifiedClassName);
        $domain = $this->extractDomainFromFQCN($fullyQualifiedClassName);

        // emptiness must be checked before processing strpos(), strpos() doesn't support empty needle
        if (empty($domain) || strpos($action, $domain) !== 0) {
            $this->results->addResult(new ValidationResult(false, "Domain '$domain' must be included in command class name."));
        }
    }

    private function checkCommandNameIsConsistentWithClassName(
        string $commandName, string $fullyQualifiedClassName): void
    {
        $actionWithoutDomain = $this->extractActionWithoutDomainFromFQCN($fullyQualifiedClassName);
        $domain = $this->extractDomainFromFQCN($fullyQualifiedClassName);

        // Command name pattern = fop:command-domain:command[:-]action
        $expectedCommandNamePattern = strtolower(
            'fop:'
            . implode('-', $this->getWords($domain))
            . ':'
            . implode('[:-]', $this->getWords($actionWithoutDomain))
        );

        if (!preg_match('/^' . $expectedCommandNamePattern . '$/', $commandName)) {
            $this->results->addResult(new ValidationResult(false, 'Wrong format for command class name.' . PHP_EOL
                . "Expected = $expectedCommandNamePattern" . PHP_EOL
                . "Actual = $commandName"));
        }
    }

    private function checkServiceNameIsConsistentWithClassName(
        string $service, string $fullyQualifiedClassName): void
    {
        $actionWithoutDomain = $this->extractActionWithoutDomainFromFQCN($fullyQualifiedClassName);
        $domain = $this->extractDomainFromFQCN($fullyQualifiedClassName);

        // Command service name pattern = fop.console.command_domain.command[\._]actionWithoutDomain.command
        $expectedCommandServiceNamePattern = strtolower(
            'fop.console.'
            . implode('_', $this->getWords($domain))
            . '.'
            . implode('[\._]', $this->getWords($actionWithoutDomain))
            . '.command'
        );

        if (!preg_match('/^' . $expectedCommandServiceNamePattern . '$/', $service)) {
            $this->results->addResult(new ValidationResult(false, "Domain can't be empty."));
        }
    }

    /**
     * Split string on each Capitalized letter.
     *
     * e.g. HelloWorld => ['Hello', 'World']
     *
     * @param string $subject
     *
     * @return array<string>
     */
    private function getWords(string $subject): array
    {
        return preg_split('/(?=[A-Z])/', ucfirst($subject), -1, PREG_SPLIT_NO_EMPTY) ?: [''];
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

    private function extractDomainFromFQCN(string $fullyQualifiedClassName): string
    {
        return $this->getFQCNRegexpMatches($fullyQualifiedClassName)['domain'] ?? '';
    }

    private function extractActionFromFQCN(string $fullyQualifiedClassName): string
    {
        return $this->getFQCNRegexpMatches($fullyQualifiedClassName)['action'] ?? '';
    }

    private function extractActionWithoutDomainFromFQCN(string $fullyQualifiedClassName): string
    {
        return str_replace(
            $this->extractDomainFromFQCN($fullyQualifiedClassName),
            '',
            $this->extractActionFromFQCN($fullyQualifiedClassName)
        );
    }
}
