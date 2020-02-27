<?php
/**
 * 2020-present Friends of Presta community
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author Friends of Presta community
 * @copyright 2020-present Friends of Presta community
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace FOP\Console\Overriders;

use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;

interface OverriderInterface
{
    /**
     * Overrider exectution.
     *
     * Creates the file(s), do the job.
     *
     * @param string $path the file or folder path to override
     * @param SymfonyStyle $io to display a message
     *
     * @throws Exception in case process fails : just throw an exception
     *
     * @todo argument $path can be something else than a path ?
     */
    public function run(string $path, SymfonyStyle $io): void;

    /**
     * Did the overrider applies to this file ?
     *
     * Just set $this->handled and let AbstractOverrider do the job.
     */
    public function isHandled(): bool;
}
