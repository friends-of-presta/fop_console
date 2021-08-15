<?php

namespace FOP\Console\Commands\Employees;

use Configuration;
use Db;
use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ListEmployees extends Command
{
    protected function configure()
    {
        $this
            ->setName('fop:employees:list')
            ->setDescription('List employees')
            ->setHelp('List employees registered in admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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
        } else {
            $io->error('No employees registered on this shop');

            return 1;
        }
    }
}
