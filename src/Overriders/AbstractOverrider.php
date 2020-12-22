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
 */

namespace FOP\Console\Overriders;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;

class AbstractOverrider
{
    /** @var bool */
    private $successful;

    /** @var bool */
    private $force = false;

    /** @var SymfonyStyle|null */
    private $io;

    /** @var bool */
    private $interactive_mode = false;

    final public function setSuccessful(): void
    {
        $this->successful = true;
    }

    final public function setUnsuccessful(): void
    {
        $this->successful = false;
    }

    final public function isSuccessful(): bool
    {
        return $this->successful;
    }

    final public function init(bool $force, bool $no_interaction, ?SymfonyStyle $io): void
    {
        $this->interactive_mode = !$no_interaction;
        $this->force = $force;
        $this->io = $io;
    }

    final public function getIo(): SymfonyStyle
    {
        if (is_null($this->io)) {
            throw new RuntimeException('Io not initialized. Init Overrider using init().');
        }

        return $this->io;
    }

    final public function IsForceMode(): bool
    {
        return $this->force;
    }

    final public function hasIo(): bool
    {
        return !is_null($this->io);
    }

    final public function isInteractiveMode(): bool
    {
        return $this->interactive_mode;
    }
}
