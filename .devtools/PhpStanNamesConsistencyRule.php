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

use FOP\Console\Tests\Validator\PhpStanNamesConsistencyService;
use FOP\Console\Tests\Validator\ValidationResult;
use PhpParser;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\NodeDumper;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Class PhpStanNamesConsistencyRule
 *
 * @template T
 * @phpstan-template \Stmt\ClassMethod
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassMethod>
 * Do not set class as final : inheritance is needed for phpstan test.
 */
class PhpStanNamesConsistencyRule implements Rule
{
    public const FOP_BASE_COMMAND_CLASS_NAME = \FOP\Console\Command::class;

    /** @var \PhpParser\Node\Stmt\ClassMethod */
    private $node;

    /** @var \PHPStan\Analyser\Scope */
    private $scope;

    /** @var \FOP\Console\Tests\Validator\PhpStanNamesConsistencyService */
    private $validator;

    public function __construct(PhpStanNamesConsistencyService $validatorService)
    {
        $this->validator = $validatorService;
    }

    /**
     * This phpstan rule process only Methods.
     */
    public function getNodeType(): string
    {
        return Stmt\ClassMethod::class;
    }

    /**
     * @param \PhpParser\Node $node
     * @param \PHPStan\Analyser\Scope $scope
     *
     * @return array<int, \PHPStan\Rules\RuleError>
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

        $commandName = $this->getCommandName();

        if (!$commandName) {
            return [RuleErrorBuilder::message('Console command name can not be extracted. Therefore consistency with classname can\'t be checked.')
                ->tip('Maybe you could use $this->setName() with a plain string to fix this error.')
                ->build(),
            ];
        }

        $commandClass = $scope->getClassReflection();
        if(is_null($commandClass)) {
            throw new \Exception('Class reflection failed.');
        }

        $commandClassName = $commandClass->getName();
        $validationResults = $this->validator->validateNames($commandClassName, $commandName);
        if(!$validationResults->isValidationSuccessful()) {
            return array_map(
                static function (ValidationResult $result) {
                    $error = RuleErrorBuilder::message($result->getMessage());
                    empty($result->getTip()) ?: $error->tip($result->getTip());

                    return $error->build();
                    },
                $validationResults->getFailures());
        }

        return [];
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

        // we might filter false positive by checking argument type.
        if ('Scalar_String' !== $setNameNode->args[0]->value->getType()) {

            return '';
        }

        return $setNameNode->args[0]->value->value ?? '';
    }

    private function nodeIsConfigureMethod(): bool
    {
        return 'configure' === $this->node->name->toString();
    }

    private function nodeIsInClassFopCommand(): bool
    {
        $class = $this->scope->getClassReflection();
        if (is_null($class)) {
            throw new \LogicException('This rule is supposed to be executed on a class\' method but no reflected class found.');
        }

        /* @var $class \PHPStan\Reflection\ClassReflection */
        return in_array(static::FOP_BASE_COMMAND_CLASS_NAME, $class->getParentClassesNames());
    }

}
