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
     *             `FOP\Console\Commands\Domain\DomainAction`
     */
    private const FQCN_REGEXP = '#^FOP\\\Console\\\Commands\\\(?<domain>[[:alpha:]]+)\\\(?<action>[[:alpha:]]+)$#X';

    /**
     * @var string regular expression for command's name
     *             `fop:domain:action`
     *             action can also have ':' and '-' in it
     */
    private const COMMAND_REGEXP = '#^fop:(?<domain>[[:alpha:]]+):(?<action>[[:alpha:]:-]+)$#X';

    /**
     * @var string regular expression for service's name
     *             `fop.console.domain.action`
     *             action can contain '.' or '_'
     */
    private const SERVICE_REGEXP = '#^fop\.console\.(?<domain>[[:alpha:]]+)\.(?<action>[[:alpha:]\._]+)\.command$#X';

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

    /**
     * Logic : extract domain and action from fqcn and compare with domain and action extracted from command name
     *
     * @param string $commandName
     * @param string $fullyQualifiedClassName
     *
     * @return void
     */
    private function checkCommandNameIsConsistentWithClassName(
        string $commandName, string $fullyQualifiedClassName): void
    {
        preg_match(self::COMMAND_REGEXP, $commandName, $matches);
        $domain = ucfirst($matches['domain'] ?? '');
        // action words : action part words split by `-` or `:`  and CamelCased (one word,  ucfirst() is ok)
        $actionWords = array_map('ucfirst', preg_split('/[:-]/', $matches['action'] ?? ''));

        $actionWordsFromFQCN = $this->getWords($this->extractActionWithoutDomainFromFQCN($fullyQualifiedClassName));
        $domainFromFQCN = $this->extractDomainFromFQCN($fullyQualifiedClassName);

        if ($domain != $domainFromFQCN || $actionWords != $actionWordsFromFQCN) {
            $this->results->addResult(
                new ValidationResult(
                    false,
                    "Wrong command name '$commandName'")
            );
        }
    }

    /**
     * Check Service Name Is Consistent With Class Name
     *
     * Logic : rebuild the fcqn from the service name then compare.
     *
     * @param string $service
     * @param string $fullyQualifiedClassName
     *
     * @return void
     */
    private function checkServiceNameIsConsistentWithClassName(
        string $service, string $fullyQualifiedClassName): void
    {
        preg_match(self::SERVICE_REGEXP, $service, $matches);
        $domain = ucfirst($matches['domain'] ?? '');
        // action words : action part words split by `-` or `:`  and CamelCased (one word,  ucfirst() is ok)
        $actionWords = array_map('ucfirst', preg_split('/[._]/', $matches['action'] ?? ''));

        $actionWordsFromFQCN = $this->getWords($this->extractActionWithoutDomainFromFQCN($fullyQualifiedClassName));
        $domainFromFQCN = $this->extractDomainFromFQCN($fullyQualifiedClassName);

        if ($domain != $domainFromFQCN || $actionWords != $actionWordsFromFQCN) {
            $this->results->addResult(
                new ValidationResult(
                    false,
                    "Wrong service name '$service'")
            );
            // @todo add a tip
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
     * @return string domain in CamelCase format `Domain`
     */
    private function extractDomainFromFQCN(string $fullyQualifiedClassName): string
    {
        return $this->getFQCNRegexpMatches($fullyQualifiedClassName)['domain'] ?? '';
    }

    /**
     * @return string domaine+action in CamelCase format `DomainAction`
     */
    private function extractActionFromFQCN(string $fullyQualifiedClassName): string
    {
        return $this->getFQCNRegexpMatches($fullyQualifiedClassName)['action'] ?? '';
    }

    /**
     * @return string action in CamelCase format `Action`
     */
    private function extractActionWithoutDomainFromFQCN(string $fullyQualifiedClassName): string
    {
        return str_replace(
            $this->extractDomainFromFQCN($fullyQualifiedClassName),
            '',
            $this->extractActionFromFQCN($fullyQualifiedClassName)
        );
    }

    /**
     * @param string $fullyQualifiedClassName
     *
     * @return array{domain?: string, action?: string}
     */
    private function getFQCNRegexpMatches(string $fullyQualifiedClassName): array
    {
        preg_match(self::FQCN_REGEXP, $fullyQualifiedClassName, $matches);

        return $matches ?? [];
    }
}
