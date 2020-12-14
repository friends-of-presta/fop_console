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

interface OverriderInterface
{
    /*
     * Overrider execution.
     *
     * Creates the file(s), do the job.
     *
     * @param string $path the file or folder path to override
     * @throws Exception in case process fails : just throw an exception
     * @return string execution message.
     */
    public function run(string $path): string;

    /**
     * Does the overrider handle this path ?
     *
     * @param string $path
     *
     * @return bool
     */
    public function handle(string $path): bool;
}
