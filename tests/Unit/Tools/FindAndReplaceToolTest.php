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

namespace FOP\Console\Tests\Unit\Tools;

use FOP\Console\Tests\Unit\CSVFileIterator;
use FOP\Console\Tools\FindAndReplaceTool;
use PHPUnit\Framework\TestCase;

class FindAndReplaceToolTest extends TestCase
{
    private $findAndReplaceTool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findAndReplaceTool = new FindAndReplaceTool();
    }

    public function testGetCasedReplacePairs()
    {
        foreach (iterator_to_array($this->csvProvider('test-get-cased-replace-pairs')) as $searchReplace) {
            $search = $searchReplace[0];
            $replace = $searchReplace[1];
            $formats = $searchReplace[2];

            $csvCasedReplacePairs = iterator_to_array($this->csvProvider('cased-replace-pairs/' . $search . '-to-' . $replace));
            $expectedCasedReplacePairs = [];
            foreach ($csvCasedReplacePairs as $replacePair) {
                $expectedCasedReplacePairs[$replacePair[0]] = $replacePair[1];
            }

            if ($formats === 'aesthetic') {
                $caseFormats = $this->findAndReplaceTool->getAestheticCasesFormats();
            } else {
                $caseFormats = $this->findAndReplaceTool->getUsualCasesFormats();
            }

            $actualCasedReplacePairs = $this->findAndReplaceTool->getCasedReplacePairs($caseFormats, $search, $replace);
            $this->assertEquals($expectedCasedReplacePairs, $actualCasedReplacePairs);
        }
    }

    /**
     * @depends testGetCasedReplacePairs
     */
    public function testFindReplacePairsInFiles()
    {
        foreach (iterator_to_array($this->csvProvider('test-find-replace-pairs-in-files')) as $searchReplace) {
            $module = $searchReplace[0];
            $search = $searchReplace[1];
            $replace = $searchReplace[2];
            $formats = $searchReplace[2];

            $csvFilesReplacePairs = iterator_to_array($this->csvProvider('files-replace-pairs/' . $module . '/' . $search . '-to-' . $replace));
            $expectedFilesReplacePairs = [];
            foreach ($csvFilesReplacePairs as $replacePair) {
                $expectedFilesReplacePairs[$replacePair[0]] = $replacePair[1];
            }

            $moduleFiles = $this->findAndReplaceTool
                ->getFilesSortedByDepth(_PS_MODULE_DIR_ . $module)
                ->exclude(['vendor', 'node_modules']);

            if ($formats === 'aesthetic') {
                $caseFormats = $this->findAndReplaceTool->getAestheticCasesFormats();
            } else {
                $caseFormats = $this->findAndReplaceTool->getUsualCasesFormats();
            }

            $actualFilesReplacePairs = $this->findAndReplaceTool->findReplacePairsInFiles(
                $moduleFiles,
                $this->findAndReplaceTool->getCasedReplacePairs($caseFormats, $search, $replace)
            );

            $this->assertEquals($expectedFilesReplacePairs, $actualFilesReplacePairs);
        }
    }

    public function csvProvider($relativePath): CSVFileIterator
    {
        return new CSVFileIterator('tests/Resources/csv/' . $relativePath . '.csv');
    }
}
