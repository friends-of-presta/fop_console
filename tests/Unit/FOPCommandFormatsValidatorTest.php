<?php

namespace FOP\Console\Tests\Unit;

use FOP\Console\FOPCommandFormatsValidator;
use PHPUnit\Framework\TestCase;

class FOPCommandFormatsValidatorTest extends TestCase
{
    /** @var FOPCommandFormatsValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new FOPCommandFormatsValidator();
    }

    /**
     * @dataProvider commandsFormatsProvider
     */
    public function testValidate($commandDomain, $commandClassName, $commandName, $commandService, $expected)
    {
        $this->assertSame(
            filter_var($expected, FILTER_VALIDATE_BOOLEAN),
            $this->validator->validate(
                $commandDomain,
                $commandClassName,
                $commandName,
                $commandService
            ),
            implode(PHP_EOL, $this->validator->getValidationMessages())
        );
    }

    public function commandsFormatsProvider(): CSVFileIterator
    {
        return new CSVFileIterator('tests/Resources/commands-formats.csv');
    }
}
