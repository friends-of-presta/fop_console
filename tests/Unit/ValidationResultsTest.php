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

use FOP\Console\Tests\Validator\Exception\CantValidateEmptyValidationResults;
use FOP\Console\Tests\Validator\ValidationResult;
use FOP\Console\Tests\Validator\ValidationResults;
use PHPUnit\Framework\TestCase;

class ValidationResultsTest extends TestCase
{
    /** @var \FOP\Console\Tests\Validator\ValidationResults */
    private $validationResults;

    protected function setUp()
    {
        $this->validationResults = new ValidationResults();
    }

    public function testIsValidationSuccessfulThrowExceptionOnEmptyValidationResults()
    {
        $this->assertTrue(class_exists(CantValidateEmptyValidationResults::class), 'Exception not implemented.');
        $this->expectException(CantValidateEmptyValidationResults::class);

        $this->validationResults->isValidationSuccessful();
    }

    public function testCollectsValidationsResult()
    {
        $this->validationResults->addResult(new ValidationResult(false, 'This is a failure message'));
        $this->validationResults->addResult(new ValidationResult(false, 'This is another failure message'));

        $this->assertCount(2, $this->validationResults);
    }

    public function testValidationResultsCanBeRetrieved()
    {
        $this->validationResults->addResult(new ValidationResult(false, 'This is a failure message'));
        $this->validationResults->addResult(new ValidationResult(false, 'This is another failure message'));

        // results can be accessed using a foreach or iterator_to_array()
        $this->assertContainsOnly(ValidationResult::class, iterator_to_array($this->validationResults));
    }

    public function testIsValidationSuccessfulReturnsTrueIfContainsOnlyPositiveResults()
    {
        $this->validationResults->addResult(new ValidationResult(true, 'This is positive message'));
        $this->validationResults->addResult(new ValidationResult(true, 'This is positive message'));

        $this->assertTrue($this->validationResults->isValidationSuccessful());
    }

    public function testIsValidationSuccessfulReturnsFalseIfContainsOneOrMoreNegativeResults()
    {
        $this->validationResults->addResult(new ValidationResult(true, 'This is positive message'));
        $this->validationResults->addResult(new ValidationResult(false, 'Boo'));
        $this->validationResults->addResult(new ValidationResult(true, 'This is positive message'));

        $this->assertFalse($this->validationResults->isValidationSuccessful());
    }

    public function testGetFailuresReturnsNegativeResults()
    {
        $negativeResult1 = new ValidationResult(false, 'Boo 2');
        $negativeResult2 = new ValidationResult(false, 'Boo');
        $positiveResult = new ValidationResult(true, 'This is positive message');

        $this->validationResults->addResult($positiveResult);
        $this->validationResults->addResult($negativeResult1);
        $this->validationResults->addResult($negativeResult2);
        $this->validationResults->addResult($positiveResult);

        $this->assertContains($negativeResult1, $this->validationResults->getFailures());
        $this->assertContains($negativeResult2, $this->validationResults->getFailures());
        $this->assertNotContains($positiveResult, $this->validationResults->getFailures());
    }
}
