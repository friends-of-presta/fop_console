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

use FOP\Console\Tests\Validator\ValidationResult;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    public function testItsConstructedWith3Parameters()
    {
        $result = new ValidationResult(false, 'something went wrong.', 'do this to fix it.');
        $this->assertTrue(is_object($result));
    }

    public function testIsSuccessfulTrue()
    {
        $result = new ValidationResult(true, 'something went wrong.', 'do this to fix it.');
        $this->assertTrue($result->isSuccessful());
    }

    public function testIsSuccessfulFalse()
    {
        $result = new ValidationResult(false, 'something went wrong.', 'do this to fix it.');
        $this->assertFalse($result->isSuccessful());
    }

    public function testHasMessageGetter()
    {
        $message = 'something went wrong.';
        $result = new ValidationResult(false, $message, 'do this to fix it.');
        $this->assertEquals($message, $result->getMessage());
    }

    public function testHasTipGetter()
    {
        $tip = 'this is a tip.';
        $result = new ValidationResult(false, 'a message', $tip);
        $this->assertEquals($tip, $result->getTip());
    }
}
