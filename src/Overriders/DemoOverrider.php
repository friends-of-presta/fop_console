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

use RuntimeException;

/**
 * This is a demo Overrider.
 * It does nothing but serve as example.
 *
 * It handles file (passed as command argument) if it contains 'classes/*README.md*'
 *
 * If file name also contains 'success', the overrider pretend to succeed.
 * use 'classes/README.md_success' for example.
 * For other cases, see comments in source code below.
 */
// using Inheritance is discouraged. Use a final class. if Inheritance is needed you will to really think about it.
final class DemoOverrider extends AbstractOverrider implements OverriderInterface
{
    /**
     * Is this path handled by this overrider ?
     *
     * Used to declare if this overrider will be triggered.
     *
     * This demo implementation returns a positive response if the path matches 'classes/*README.md*' pattern.
     *
     * @return bool
     */
    public function handle(): bool
    {
        return fnmatch('classes/*README.md*', $this->getPath());
    }

    /**
     * Overrider execution.
     *
     * Executed only if declared to handle the path.
     *
     * @return array<string>
     *
     * @throws RuntimeException
     */
    public function run(): array
    {
        // at this point you are sure that path is matching fnmatch('classes/*README.md*', $this->getPath())
        // handle was called as a filter before.

        // Concrete process goes here : copy or create files.
        // here are just responses example no copy will happen.

        // Case 1 : Success : Copy/Creation succeed.
        if (0 < strpos($this->getPath(), 'success')) {
            $this->setSuccessful();

            $return_messages = [];
            if (false !== strpos($this->getPath(), 'maybe')) {
                $return_messages += ['You confirmed the run or used the --force option.'];
            }

            return $return_messages + ['Success :) . File xxx created. Do something great with it!', 'Try with "classes/README.md_failure" for another result.'];
        }

        // Case 2 : smooth failure : file already exists for example.
        if (false !== strpos($this->getPath(), 'failure')) {
            $this->setUnsuccessful();

            return ['Error. Oops something happen',
                    'Try with "classes/README.md_success_maybe" for another result.', ];
        }

        // something unexpected happened !
        // just Throw exception ( MakeOverride handle it )
        throw new RuntimeException(__CLASS__ . ' has failed. Try with "fop:override classes/README.md_success" .');
        // @todo Maybe add an OverriderException
    }

    public function getDangerousConsequences(): ?string
    {
        // !! this is an example, in fact it will be a file / method existence check.
        if (false !== strpos($this->getPath(), 'maybe')) {
            return 'The file xxx Already exists. Overwrite it ?';
        }

        return null;
    }
}
