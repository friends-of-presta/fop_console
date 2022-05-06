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

namespace FOP\Console\Overriders;

use Exception;

interface OverriderInterface
{
    /**
     * Overrider execution.
     *
     * Creates the file(s), do the job.
     *
     * @throws Exception in case of hard fail. In case of soft fails just return message(s)
     *
     * @see isSuccessful()
     *
     * @return array<string> messages. Error or success messages depends on $this->isSuccessful()
     */
    public function run(): array;

    /**
     * Does the overrider handle this path ?
     *
     * @return bool
     */
    public function handle(): bool;

    /**
     * Was the execution successful ?
     * Response set by run().
     *
     * @return bool
     */
    public function isSuccessful(): bool;

    /**
     * Returns values expected :
     * - null : nothing bad can happen.
     * - string : description of what will happen if user confirms.
     *   This is probably a file overwrite.
     *   Message example : 'The file xxx will be overwritten.'
     *
     * If a string is returned the user will be prompted for confirmation (interactive mode only)
     * , unless force option is defined.
     * Otherwise, the overrider is not ran. Nothing happen.
     *
     * @todo Maybe returning an array is better : consistent with run(). But in fact, there will be only a single consequence...
     *
     * @return string|null
     */
    public function getDangerousConsequences(): ?string;

    public function init(string $path): void;
}
