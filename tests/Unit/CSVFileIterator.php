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

namespace FOP\Console\Tests\Unit;

final class CSVFileIterator implements \Iterator
{
    private $file;
    private $key = 0;
    private $current;

    public function __construct(string $file)
    {
        $this->file = fopen($file, 'r');
    }

    public function __destruct()
    {
        fclose($this->file);
    }

    public function rewind(): void
    {
        rewind($this->file);

        $this->current = fgetcsv($this->file);

        $this->key = 0;
    }

    public function valid(): bool
    {
        return !feof($this->file);
    }

    public function key(): int
    {
        return $this->key;
    }

    public function current(): array
    {
        return $this->current;
    }

    public function next(): void
    {
        do {
            $this->current = fgetcsv($this->file);
            ++$this->key;
        } while ($this->valid() && $this->current === [null]);
    }
}
