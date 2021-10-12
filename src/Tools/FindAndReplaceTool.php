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

namespace FOP\Console\Tools;

use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FindAndReplaceTool
{
    /**
     * @var array
     */
    const USUAL_SEPARATORS = [' ', '_', '.', ',', '/'];

    /**
     * Get words separated by separators if provided, otherwise following pascal case format.
     *
     * @param string $subject
     * @param array|string $separators
     *
     * @return array $words
     */
    public function getWords($subject, $separators = [])
    {
        if (!is_array($separators)) {
            $separators = [$separators];
        }

        $regExp = '';
        if (empty($separators)) {
            /**
             * @see https://stackoverflow.com/a/3103795
             */
            $regExp = '(?<=[A-Z])(?=[A-Z][a-z])' .  // UC before me, UC lc after me
                '|(?<=[^A-Z])(?=[A-Z])' .           // Not UC before me, UC after me
                '|(?<=[A-Za-z])(?=[^A-Za-z])';      // Letter before me, non letter after me
        } else {
            $regExp = '[\\' . implode('\\', $separators) . ']+';
        }

        $words = preg_split('/' . $regExp . '/', $subject, -1, PREG_SPLIT_NO_EMPTY);

        if (!$words) {
            throw new RuntimeException("Failed to retrieve words from $subject");
        }

        return $words;
    }

    /**
     * Return a functions array to format strings in usual case formats.
     * Order is important: if two case formats results are equal, the last will win.
     * When in doubt, put the most common/logic ones last.
     *
     * @return array $caseFormats formatting functions array
     */
    public function getUsualCasesFormats()
    {
        $caseFormats = [
            //string_to_format
            'snakeCase' => function ($words) {
                return strtolower(implode('_', $words));
            },
            //stringtoformat
            'lowerCase' => function ($words) {
                return strtolower(implode($words));
            },
            //string-to-format
            'kebabCase' => function ($words) {
                return strtolower(implode('-', $words));
            },
            //STRINGTOFORMAT
            'upperCase' => function ($words) {
                return strtoupper(implode($words));
            },
            //Stringtoformat
            'firstUpperCased' => function ($words) {
                return ucfirst(strtolower(implode($words)));
            },
            //String To Format
            'pascalCaseSpaced' => function ($words) {
                return implode(' ', array_map('ucfirst', $words));
            },
            //String to Format
            'spaced' => function ($words) {
                return implode(' ', $words);
            },
            //stringToFormat
            'camelCase' => function ($words) {
                return lcfirst(
                    implode(
                        array_map('ucfirst',
                            array_map('strtolower', $words)
                        )
                    )
                );
            },
            //StringToFormat
            'pascalCase' => function ($words) {
                return implode(array_map('ucfirst', $words));
            },
            //STRING_TO_FORMAT
            'upperCaseSnakeCase' => function ($words) {
                return strtoupper(implode('_', $words));
            },
        ];

        return $caseFormats;
    }

    /**
     * Reorder case formats to focus on aesthetics over code occurences (can introduce errors if used in code files!).
     *
     * @return array $aestheticCaseFormats formatting functions array
     */
    public function getAestheticCasesFormats()
    {
        $usualCaseFormats = $this->getUsualCasesFormats();

        $aestheticCaseFormatsOrder = [
            'camelCase',
            'lowerCase',
            'snakeCase',
            'upperCase',
            'firstUpperCased',
            'pascalCaseSpaced',
            'spaced',
            'kebabCase',
            'pascalCase',
            'upperCaseSnakeCase',
        ];

        $aestheticCaseFormats = [];
        foreach ($aestheticCaseFormatsOrder as $formatName) {
            $aestheticCaseFormats[$formatName] = $usualCaseFormats[$formatName];
        }

        return $aestheticCaseFormats;
    }

    /**
     * Format search and replace with provided case formats
     *
     * @param array $caseFormats
     * @param string $search
     * @param string $replace
     *
     * @return array $replacePairs search => replace
     */
    public function getCasedReplacePairs($caseFormats, $search, $replace)
    {
        $wordsFunctions = [
            'singleWord' => function ($subject) {
                return [$subject];
            },
            'pascalCaseWords' => function ($subject) {
                return $this->getWords($subject);
            },
            'usualWords' => function ($subject) {
                return $this->getWords($subject, self::USUAL_SEPARATORS);
            },
        ];

        $replacePairs = [];

        $hasSeparatorRegExp = '/[\\' . implode('\\', self::USUAL_SEPARATORS) . ']+/';
        $hasSeparator = preg_match($hasSeparatorRegExp, $search) === 1
            || preg_match($hasSeparatorRegExp, $replace) === 1;

        if ($hasSeparator) {
            unset($wordsFunctions['pascalCaseWords']);
        } else {
            unset($wordsFunctions['usualWords']);
        }

        foreach ($wordsFunctions as $wordsFunction) {
            $searchWords = $wordsFunction($search);
            $replaceWords = $wordsFunction($replace);

            foreach ($caseFormats as $caseFormat) {
                $replacePairs[$caseFormat($searchWords)] = $caseFormat($replaceWords);
            }
        }

        return $replacePairs;
    }

    /**
     * Return replace pairs for which the search keyword was found in provided set of files.
     *
     * @param Finder $files
     * @param array $replacePairs search => replace
     *
     * @return array $foundReplacePairs
     */
    public function findReplacePairsInFiles($files, $replacePairs)
    {
        $foundReplacePairs = [];

        $iterator = $files->getIterator();
        $iterator->rewind();
        while ($iterator->valid() && count($foundReplacePairs) < count($replacePairs)) {
            $file = $iterator->current();

            $foundReplacePairs += $this->findReplacePairsInFile($file, $replacePairs);

            $iterator->next();
        }

        return $foundReplacePairs;
    }

    /**
     * Return replace pairs for which the search keyword was found in provided file.
     *
     * @param SplFileInfo $file
     * @param array $replacePairs search => replace
     *
     * @return array $fileReplacePairs
     */
    public function findReplacePairsInFile($file, $replacePairs)
    {
        $filePath = $file->getRelativePathname();
        $fileContent = '';
        if ($file->isFile()) {
            $fileContent = $file->getContents();
        }

        $fileReplacePairs = [];

        foreach ($replacePairs as $search => $replace) {
            if (str_contains($filePath, $search)
                || ($file->isFile() && str_contains($fileContent, $search))
            ) {
                $fileReplacePairs[$search] = $replace;
            }
        }

        return $fileReplacePairs;
    }

    /**
     * Return files set ordered by path depth
     *
     * @param array|string $paths
     *
     * @return Finder
     */
    public function getFilesSortedByDepth($paths)
    {
        $finder = new Finder();

        $finder
            ->sort(function (\SplFileInfo $a, \SplFileInfo $b) {
                $depth = substr_count($a->getRealPath(), '/') - substr_count($b->getRealPath(), '/');

                return ($depth === 0) ? strlen($a->getRealPath()) - strlen($b->getRealPath()) : $depth;
            })
            ->in($paths);

        return $finder;
    }
}
