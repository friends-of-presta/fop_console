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

namespace FOP\Console\DevTools;

use Doctrine\Common\Inflector\Inflector;
use FOP\Console\Tests\Validator\PhpStanNamesConsistencyService;
use PhpParser;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\NodeDumper;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Class PhpStanCustomRule
 *
 * @template T
 * @phpstan-template Stmt\ClassMethod
 * @implements PHPStan\Rules\Rule<Node\Stmt\ClassMethod>
 */
class PhpStanCustomRule implements Rule
{
    const FOP_BASE_COMMAND_CLASS_NAME = \FOP\Console\Command::class;

    /** @var int|null @todo rename */
    private $setName_line;

    /** @var PhpParser\Node\Stmt\ClassMethod */
    private $node;

    /** @var \PHPStan\Analyser\Scope */
    private $scope;

    /** @var bool For dev/debug purpose only */
    private $verbose = true;

    /** @var \FOP\Console\Tests\Validator\PhpStanNamesConsistencyService */
    private $validatorService;

    public function __construct(PhpStanNamesConsistencyService $validatorService)
    {
        $this->validatorService = $validatorService;
    }

    /**
     * This phpstan rule process only Methods.
     */
    public function getNodeType(): string
    {
        return Stmt\ClassMethod::class;
    }

    /**
     * @param Stmt\ClassMethod $node
     * @param Scope $scope
     *
     * @return array<string>
     *
     * @throws \PHPStan\ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // filtering processed earlier @see self::getNodeType()
        // $node is a Stmt\ClassMethod
        $this->node = $node;
        $this->scope = $scope;

        /* @var Stmt\ClassMethod $node */
        if (!$this->nodeIsConfigureMethod()
            || !$this->nodeIsInClassFopCommand()) {
            return [];
        }

        $commandServiceName = 'not set';
        $commandName = $this->getCommandName();

        if (!$commandName) {
            if ($this->verbose) {
                dump($this->scope->getFile());
                dump($commandName);
            }

            return [RuleErrorBuilder::message('Console command name can not be extracted. Therefore consistency with classname can\'t be checked.')
                ->tip('Maybe you could use $this->setName() with a plain string to fix this error.')
                ->build(),
            ];
        }

        $commandDomain = '?'; // where should it be found ?
        $commandClassName = $scope->getClassReflection()->getName();
        $commandServiceName = $this->getServiceNameForClass($commandClassName);

        $this->validatorService->ditBonjour();
//        $validationResult = $this->validatorService->validate($commandDomain, $commandClassName, $commandName, $commandServiceName);

//        dump($validationResult);
//        echo ' wip Ready to check ... '.PHP_EOL;
        return [];

        $class_name = (string) str_replace('FOP\Console\Commands\\', '', $scope->getClassReflection()->getName()); // with namespace amputé de \FOP\Console

        return $errors;
    }

    private function getServiceNameForClass(string $className): string
    {
        return 'nop'; // @todo implement me
    }

    /**
     * Get the console command name.
     *
     * e.g. fop:domain:action
     * To find it, we search for the `setName` function call into the `configure` method.
     *
     * Possible future problem : a methode setName() is used in the configure method but not on 'this' ...
     *
     * @return string
     */
    private function getCommandName(): string
    {
        $nodeFinder = new PhpParser\NodeFinder();

        $searchSetNameMethodCallFilter = function (Node $node) {
            if ($node->getType() !== 'Expr_MethodCall') {
                return false;
            }

            /** @var Expr\MethodCall $node */
            if ($node->name->toString() !== 'setName') {
                return false;
            }

            return true;
        };
        /** @var Expr\MethodCall $setNameNode */
        $setNameNode = $nodeFinder->findFirst($this->node->getStmts(), $searchSetNameMethodCallFilter);

        if (is_null($setNameNode)) {
            return '';
        }

        $this->setName_line = $setNameNode->getLine();

        // we might filter false positive by checking argument type.
        if ('Scalar_String' !== $setNameNode->args[0]->value->getType()) {
            $this->debug('setName() found but argument is not a string.');
            $this->debugNode($setNameNode);

            return '';
        }

//        $d = new NodeDumper(); // pour debug
//        echo dump();
//        throw new \Exception('ok si scalar_string mais sinon ?? cf Image\Abstract : proceder par reflexion ?');

        return $setNameNode->args[0]->value->value ?? '';
    }

    /**
     * @param string $relative_file_path [Domain/]Command.php
     * @param string $class_name Command
     * @param string $command_name fop:[domain:]command
     *                             - check command name against filename
     *                             - check namespace against file directory
     *                             - check classname against filename
     *                             Some tests may be unnecessary because of psr-4
     *
     * @return array<string>|null null if ok; else  array of errors strings
     *
     * @throws \Exception
     */
    private function checkConsistency(string $relative_file_path, string $class_name, string $command_name): ?array
    {
        // extract command and domain from command
        $command_command = ''; // command name extracted from $command_name
        $command_domain = ''; // command domain extracted from $command_name

        $matches = [];
        if (preg_match('/fop:([a-z-]*):([a-z-]*)/', $command_name, $matches)) {
            $command_command = array_pop($matches);
            $command_domain = array_pop($matches);

            // check that values are correctly extracted
            $rebuild_command = sprintf('fop:%s:%s', $command_domain, $command_command);
            if ($rebuild_command !== $command_name) {
                dump($command_name, $command_command, $command_domain); /* @phpstan-ignore-error */
                throw new \Exception('Failed to extract command parts');
            }
            unset($rebuild_command);
        }

        if (!$command_command && preg_match('/fop:([a-z-]*)/', $command_name, $matches)) {
            $command_command = array_pop($matches);

            // check that values are correctly extracted
            $rebuild_command = sprintf('fop:%s', $command_command);
            if ($rebuild_command !== $command_name) {
                dump($command_name, $command_command, $command_domain);
                throw new \Exception('Failed to extract command parts');
            }
            unset($rebuild_command);
        }

        // --- check command name against filename
        $command_name_from_file_name = $this->getCommandNameFromFileName($relative_file_path);

        if ($command_name_from_file_name !== $command_command) {
            $errors[] = sprintf('Command name "%s" inconsistent with filename "%s"', $command_name, $relative_file_path);
            $errors[] = sprintf('debug : Current command name "%s" is inconsistent with generated value "%s"', $command_command, $command_name_from_file_name);
        }

        // --- check namespace against file directory

        // --- check classname against filename

        return empty($errors) ? null : $errors;
    }

    /**
     * @param string $relative_file_path [Domain/]CommandName.php
     *
     * @return string command-name
     */
    private function getCommandNameFromFileName(string $relative_file_path): string
    {
        $inflector = new Inflector();

        return str_replace('_', '-', $inflector->tableize(basename($relative_file_path, '.php'))); // CommandName
    }

    private function nodeIsConfigureMethod(): bool
    {
//        $this->debug(__FUNCTION__.' : '.$this->method->name->toString());
        return 'configure' === $this->node->name->toString();
    }

    private function nodeIsInClassFopCommand(): bool
    {
        $class = $this->scope->getClassReflection();
        if (is_null($class)) {
            throw new \LogicException('This rule is supposed to be executed on a class\' method but no reflected class found.');
        }

        /* @var $class \PHPStan\Reflection\ClassReflection */
        return in_array(self::FOP_BASE_COMMAND_CLASS_NAME, $class->getParentClassesNames());
    }

    private function debug($output): void
    {
        $this->verbose && var_export($output);
        $this->verbose && var_export(PHP_EOL);
    }

    private function debugNode($node)
    {
        $nd = new NodeDumper();
        dump($nd->dump($node));
    }
}

class CommandName
{
    public $domain = '';
    public $command = '';
}
