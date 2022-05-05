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

namespace FOP\Console\Commands\Employee;

use Employee;
use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Tools;
use Validate;

final class EmployeeChangePassword extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('fop:employee:change-password')
            ->setDescription('Change employee password')
            ->setHelp('Change employee password')
            ->addOption('email', 'email', InputOption::VALUE_OPTIONAL, 'Employee email')
            ->addOption('password', 'pass', InputOption::VALUE_OPTIONAL, 'Employee password');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getOption('email');
        $password = $input->getOption('password');

        if (null === $email || !Validate::isEmail($email)) {
            $userQuestion = new Question('employee email ', null);
            $userQuestion->setValidator(function ($answer) {
                if (!Validate::isEmail($answer)) {
                    throw new \RuntimeException('Invalid email');
                }

                return $answer;
            });
            $email = $this->io->askQuestion($userQuestion);
        }

        if (!Employee::employeeExists($email)) {
            $this->io->error('There is no employee with this email.');

            return 1;
        }

        if (null === $password || !Validate::isPlaintextPassword($password)) {
            $passwordQuestion = $this->getPasswordQuestion('password ');
            $password = $this->io->askQuestion($passwordQuestion);

            $passwordConfirmQuestion = $this->getPasswordQuestion('confirm password ');
            $passwordConfirm = $this->io->askQuestion($passwordConfirmQuestion);

            if ($password !== $passwordConfirm) {
                $this->io->error('Password and password confirmation do not match');

                return 1;
            }
        }

        try {
            $employee = new Employee();
            $employee->getByEmail($email);
            $employee->passwd = Tools::hash($password);
            $employee->save();
        } catch (\Exception $e) {
            $this->io->error(
                sprintf(
                    'Unable to change customer password , error : %s',
                    $e->getMessage()
                )
            );

            return 1;
        }
        $this->io->success('Password changed with success for user ' . $email);

        return 0;
    }

    /**
     * Get Password question
     *
     * @param string $label
     *
     * @return Question
     */
    protected function getPasswordQuestion(string $label): Question
    {
        $passwordQuestion = new Question($label, 'admin123456');
        $passwordQuestion->setValidator(function ($answer) {
            if (!Validate::isPlaintextPassword($answer)) {
                throw new \RuntimeException(sprintf('Your password need at least %d characters', Validate::PASSWORD_LENGTH));
            }

            return $answer;
        });
        $passwordQuestion->setHidden(true);

        return $passwordQuestion;
    }
}
