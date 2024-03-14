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

namespace FOP\Console\Commands\Util;

use Exception;
use FOP\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Dump extends Command
{
    public function configure()
    {
        $this->setName('fop:util:dump')
            ->setDescription('Dump database objects.');
        // @todo usage, ect
        $this->addArgument('entity', InputArgument::REQUIRED, 'the entity to dump (eg. product)')
            ->addArgument('id_value', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityName = ucfirst(strtolower($input->getArgument('entity')));
        $idValue = intval($input->getArgument('id_value'));

        try {
            if (!class_exists($entityName)) {
                throw new Exception("Class $entityName not found");
            }

            $object = new $entityName($idValue);
            if (!\Validate::isLoadedObject($object)) {
                throw new Exception('Object not found in database');
            }
            dump(json_decode(json_encode($object))); // fast hack : decode + encode just to keep public properties

            return 0;
        } catch (Exception $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');

            return 1;
        }
    }
}
