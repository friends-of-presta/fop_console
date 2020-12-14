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

/**
 * This is a demo Overrider.
 * It does nothing but serve as example.
 *
 * It handles files (passed as command argument).
 * If it contains 'README.md' the overrider will handle the override.
 * It means it is supposed to do something while this file is passed as argument.
 * So it declare it will process setting $this->handled to true.
 *
 * If file name also contains 'success', the overrider pretend to succeed.
 * Else it fails (throw an Exception).
 */
final class DemoOverrider implements OverriderInterface
{
    /**
     * Overrider execution.
     *
     * Executed only if declared to handle the path.
     *
     * @param string $path
     *
     * @return string
     *
     * @throws \Exception
     */
    public function run(string $path): string
    {
        // process
        // copy files, file creations, etc

        // handle process success here.
        if (0 < strpos($path, 'success')) {
            // a simple text is enough, Command will do the rest.
            return __CLASS__ . ' success. File xxx created. Do something great with it!';
        }

        // failure example
        if (0 < strpos($path, 'failure')) {
            // @todo Maybe add an OverriderException
            throw new \Exception(__CLASS__ . ' has failed. Try with "fop:override README.md_success" .');
        }

        // smooth failure example
        return __CLASS__ . ' error. Oops something happend. Maybe file already exists.! Try with README.md_failure';
    }

    /**
     * Handles only path that contains 'README.md'
     *
     * @param string $path
     *
     * @return bool
     */
    public function handle(string $path): bool
    {
        return 0 === strpos($path, 'README.md');
    }
}
