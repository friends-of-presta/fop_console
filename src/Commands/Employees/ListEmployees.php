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

namespace FOP\Console\Commands\Employees;

use Configuration;
use Db;
use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ListEmployees extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('fop:employees:list')
            ->setDescription('List employees')
            ->setHelp('List employees registered in admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        //Function Employee::getEmployees() has not enough information , use db query instead
        $employeesQuery = 'SELECT e.email,e.firstname,e.lastname,e.active,e.last_connection_date,p.name
                           FROM ' . _DB_PREFIX_ . 'employee e
                           LEFT JOIN ' . _DB_PREFIX_ . 'profile_lang p ON ( 
                           e.id_profile = p.id_profile AND p.id_lang=' . Configuration::get('PS_LANG_DEFAULT')
            . ')';

        $employees = Db::getInstance()->executeS($employeesQuery);
        if ($employees) {
            $io->title('Registered employees');
            $values = [];
            foreach ($employees as $employee) {
                $values[] =
                    [
                        $employee['email'],
                        $employee['firstname'],
                        $employee['lastname'],
                        $employee['name'],
                        $employee['active'],
                        $employee['last_connection_date'],
                    ];
            }
            $io->table(
                ['email', 'firstname', 'lastname', 'profile', 'active', 'last_connection_date'],
                $values
            );

            return 0;
        } else {
            $io->error('No employees registered on this shop');

            return 1;
        }
    }
}
