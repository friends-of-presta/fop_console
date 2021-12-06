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

use ArrayIterator;
use FOP\Console\Tests\Validator\Exception\CantValidateEmptyValidationResults;
use Iterator;
use IteratorAggregate;

/**
 * Class ValidationResults
 *
 * @implements IteratorAggregate<ValidationResult>
 */
class ValidationResults implements IteratorAggregate
{
    /**
     * @var array<int, ValidationResult>
     */
    private $results = [];

    public function isValidationSuccessful(): bool
    {
        if (empty($this->results)) {
            throw new CantValidateEmptyValidationResults();
        }

        return array_reduce($this->results, function (bool $successful, ValidationResult $result) {
            return $successful && $result->isSuccessful();
        }, true);
    }

    /**
     * @return \Iterator<ValidationResult>
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->results);
    }

    public function addResult(ValidationResult $result): void
    {
        $this->results[] = $result;
    }

    /**
     * @return array<int, \FOP\Console\Tests\Validator\ValidationResult>
     */
    public function getFailures(): array
    {
        return array_filter(
            iterator_to_array($this),
            function (ValidationResult $result) {
                return !$result->isSuccessful();
            }
        );
    }
}
