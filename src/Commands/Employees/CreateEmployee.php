<?php
/**
 * 2019-present Friends of Presta community
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author Friends of Presta community
 * @copyright 2019-present Friends of Presta community @license https://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace FOP\Console\Commands\Employees;

use Configuration;
use Employee;
use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tools;
use Validate;

class CreateEmployee extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:employee:create')
            ->setDescription('Create a new employee')
            ->setHelp('Create a new employee ')
            ->addOption('email', 'email', InputOption::VALUE_OPTIONAL, 'Employee email')
            ->addOption('password', 'pass', InputOption::VALUE_OPTIONAL, 'Employee password')
            ->addOption('firstname', null, InputOption::VALUE_OPTIONAL, 'EMployee firstname', 'admin')
            ->addOption('lastname', null, InputOption::VALUE_OPTIONAL, 'EMployee lastname', 'admmin');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $email = $input->getOption('email');
        $password = $input->getOption('password');
        $firstname = $input->getOption('firstname');
        $lastname = $input->getOption('lastname');

        if (null === $email || !Validate::isEmail($email)) {
            $userQuestion = new Question('user email :', false);
            $userQuestion->setValidator(function ($answer) {
                if (!Validate::isEmail($answer)) {
                    throw new \RuntimeException('Invalid email');
                }

                return $answer;
            });
            $email = $helper->ask($input, $output, $userQuestion);
        }

        if (Employee::employeeExists($email)) {
            $io->error('Employee with this email already exists');

            return 1;
        }

        if (null === $password || !Validate::isPlaintextPassword($password)) {
            $passwordQuestion = $this->getPasswordQuestion('admin password');
            $password = $helper->ask($input, $output, $passwordQuestion);

            $passwordConfirmQuestion = $this->getPasswordQuestion('confirm admin password');
            $passwordConfirm = $helper->ask($input, $output, $passwordConfirmQuestion);

            if ($password !== $passwordConfirm) {
                $io->error('Password and password confirmation do not match');

                return 1;
            }
        }

        if (!Validate::isName($firstname)) {
            $firstname = $helper->ask($input, $output, $this->getNameQuestion('firstname'));
        }

        if (!Validate::isName($lastname)) {
            $lastname = $helper->ask($input, $output, $this->getNameQuestion('lastname'));
        }

        try {
            $employee = new Employee();
            $employee->active = 1;
            $employee->email = $email;
            $employee->passwd = Tools::hash($password);
            $employee->firstname = $firstname;
            $employee->lastname = $lastname;
            $employee->id_lang = Configuration::get('PS_LANG_DEFAULT');
            $employee->id_profile = _PS_ADMIN_PROFILE_;
            $employee->default_tab = 1;
            $employee->bo_theme = 'default';
            $employee->save();
        } catch (PrestaShopException $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->success('New user ' . $email . ' created');

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
        $passwordQuestion = new Question($label . ' :', 'admin123456');
        $passwordQuestion->setValidator(function ($answer) {
            if (!Validate::isPlaintextPassword($answer)) {
                throw new \RuntimeException(sprintf('Your password need at least %d characters', Validate::PASSWORD_LENGTH));
            }

            return $answer;
        });
        $passwordQuestion->setHidden(true);

        return $passwordQuestion;
    }

    /**
     * Question for firstname and lastname
     *
     * @param string $label
     *
     * @return Question
     */
    protected function getNameQuestion(string $label): Question
    {
        $question = new Question($label . ' :', 'admin');
        $question->setValidator(function ($answer) use ($label) {
            if (!Validate::isName($answer)) {
                throw new \RuntimeException(sprintf('Your %s is not valid', $label));
            }

            return $answer;
        });

        return $question;
    }
}
