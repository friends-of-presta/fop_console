<?php

declare(strict_types=1);

namespace FOP\Console\DevTools;

use Doctrine\Common\Inflector\Inflector;
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
 */
class PhpStanCustomRule implements Rule
{
    /**
     * @var int|null
     */
    private $setName_line;

    public function getNodeType(): string
    {
        return Stmt\ClassMethod::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     *
     * @return array<string>
     *
     * @throws \PHPStan\ShouldNotHappenException
     *
     * @todo si une autre class implemente 'configure' ça va poser problème.
     */
    public function processNode(Node $node, Scope $scope): array
    {
        /** @var Stmt\ClassMethod $node */
        // process only 'configure' method
        if ('configure' !== $node->name->toString()) {
            return [];
        }
        // only non abstract methods
        if (is_null($scope->getClassReflection())
            || $scope->getClassReflection()->isAbstract()) {
            return [];
        }

        $command_name = $this->getCommandName($node);
        $class_name = (string) str_replace('FOP\Console\Commands\\', '', $scope->getClassReflection()->getName()); // with namespace amputé de \FOP\Console
        $relative_file_path = (string) substr($scope->getFile(), strrpos($scope->getFile(), '/src/Commands/') + strlen('/src/Commands/'));

        if (!$command_name) {
            return [RuleErrorBuilder::message('Impossible de determiner le nom de la commande')
                ->tip('Utiliser directement une chaine de caractères ou demander de l\'aide sur github')
                ->line($this->setName_line)
                ->build()->getMessage(), ];
        }

        // test du format du nom de commande
        // @todo peut être amélioré en utilisant un regexp, cf checkConsistency()
        if (strpos($command_name, 'fop:') !== 0) {
            $node->setAttribute('startLine', $this->setName_line);

            return [RuleErrorBuilder::message('Le nom de fonction doit commencer par "fop:" | Trouvé : ' . $command_name)->build()->getMessage()];
        }

        $errors = $this->checkConsistency($relative_file_path, $class_name, $command_name) ?? [];
        array_walk($errors, function (string &$error) {
            $error = RuleErrorBuilder::message($error)->nonIgnorable()->build();
        });

        return $errors;
    }

    private function getCommandName($node)
    {
        $finder = new PhpParser\NodeFinder();
        // obtenir l'appel de la methode setName
        $cb = function (Node $node) {
            /** @var Expr\MethodCall $node */
            if ($node->getType() !== 'Expr_MethodCall') {
                return false;
            }

            if ($node->name->toString() !== 'setName') {
                return false;
            }

            return true;
        };
        /** @var Expr\MethodCall $setNameNode */
        $setNameNode = $finder->findFirst($node->getStmts(), $cb);

        if (is_null($setNameNode)) {
            return null;
        }

        $this->setName_line = $setNameNode->getLine();

        if ('Scalar_String' !== $setNameNode->args[0]->value->getType()) {
            return null;
        }

//        $d = new NodeDumper(); // pour debug
//        echo dump();
//        throw new \Exception('ok si scalar_string mais sinon ?? cf Image\Abstract : proceder par reflexion ?');

        return $setNameNode->args[0]->value->value ?? null;
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
                dump($command_name, $command_command, $command_domain); /** @phpstan-ignore-error */
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
}

class CommandName
{
    public $domain = '';
    public $command = '';
}
