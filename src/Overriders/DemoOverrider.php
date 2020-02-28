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

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This is a demo Overrider.
 * It does nothing but serve as example.
 *
 * It handles files (passed as command argument).
 * If it contains 'README.md' the overrider will handle the override.
 * It means it is supposed to do something while this file is passed as argument.
 * So it declare it will process setting $this->handled to true.
 *
 * @todo Maybe not the best way to declare it is concerned.
 * Otherwise the Overrider declares not to handle this file (do not touch $this->handled.
 *
 * If file name also contains 'success', the overrider pretend to succeed.
 * Else it fails (throw an Exception).
 */
final class DemoOverrider extends AbstractOverrider implements OverriderInterface
{
    public function run(string $path, SymfonyStyle $io): void
    {
        // this overrider applies only on README.md files
        if (0 === strpos($path, 'README.md')) {
            // process files copies, file creations, etc
            // maybe $this->handled should be set latter, depends on the override process.
            $this->handled = true;
            // process override operations here
            // ...

            // handle process success here.
            if (0 < strpos($path, 'success')) {
                // a simple text is enough, Command will do the rest.
                $io->text(__CLASS__ . ' success. File xxx created. Do something great with it!');

                return;
            }

            throw new \Exception(__CLASS__ . ' has failed. Try with "fop:override README.md_success" .');
            // @todo Make OverrideException class
        }
        // otherwise : do nothing.
        // Real overriders will remain silent.
        $io->text(sprintf('%s did nothing. It does not process file/path %s. %s Try "fop:override README.md"', __CLASS__, $path, PHP_EOL));
    }
}
