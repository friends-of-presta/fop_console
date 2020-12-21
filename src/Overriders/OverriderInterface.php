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
