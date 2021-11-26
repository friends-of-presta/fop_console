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

namespace FOP\Console\Tests\Unit;

use FOP\Console\Tests\Validator\FOPCommandFormatsValidator;
use FOP\Console\Tests\Validator\ValidationResult;
use FOP\Console\Tests\Validator\ValidationResults;
use PHPUnit\Framework\TestCase;

class FOPCommandFormatsValidatorTest extends TestCase
{
    /** @var FOPCommandFormatsValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new FOPCommandFormatsValidator();
    }

    public function testValidateReturnsInstanceOfValidationResults()
    {
        $this->assertInstanceOf(
            ValidationResults::class,
            $this->validator->validate('fqcn', 'command:name', 'fop.service')
        );
    }

    /**
     * @dataProvider commandsFormatsProvider
     */
    public function testValidate($commandFQCN, $commandName, $commandService, $expected)
    {
        $results = $this->validator->validate(
            $commandFQCN,
            $commandName,
            $commandService
        );

        $successful = $results->isValidationSuccessful();
        $messages = array_reduce($results->getFailures(), function ($messages, ValidationResult $result) {
            return $messages . $result->getMessage() . PHP_EOL;
        }, '');

        $this->assertSame(
            filter_var($expected, FILTER_VALIDATE_BOOLEAN),
            $successful,
            $messages
        );
    }

    /**
     * @dataProvider commandsFormatsProviderRealWorld
     */
    public function testValidateCurrents($commandFQCN, $commandName, $commandService, $expected)
    {
        $this->testValidate($commandFQCN, $commandName, $commandService, $expected);
    }

    public function commandsFormatsProvider(): CSVFileIterator
    {
        return new CSVFileIterator('tests/Resources/commands-formats.csv');
    }

    public function commandsFormatsProviderRealWorld(): CSVFileIterator
    {
        return new CSVFileIterator('tests/Resources/commands-realworld.csv');
    }
}
