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

namespace FOP\Console\Commands\Hook;

use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Validate;

final class HookAdd extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('fop:hook:add')
            ->setAliases(['fop:add-hook'])
            ->setDescription('Create hook in database')
            ->setHelp('This command allows you create a new hook in database,
            you dont need to graft a module on it!');
        $this->addOption(
            'name',
            null,
            InputOption::VALUE_OPTIONAL,
            'Name for the new Hook'
        );
        $this->addOption(
            'title',
            null,
            InputOption::VALUE_OPTIONAL,
            'Title for the Hook'
        );
        $this->addOption(
            'description',
            null,
            InputOption::VALUE_OPTIONAL,
            'Description for the Hook'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $name = $input->getOption('name') ?? $helper->ask($input, $output, $this->getHookNameQuestion());
        $title = $input->getOption('title') ?? $helper->ask($input, $output, new Question('<question>Give me the title</question>'));
        $description = $input->getOption('description') ?? $helper->ask($input, $output, new Question('<question>Give me the description</question>'));

        try {
            if (empty($name) || empty($title) || empty($description)) {
                throw new \Exception('You must give me a name, title and description !');
            }

            $hook = new \Hook();
            $hook->name = $name;
            $hook->title = $title;
            $hook->description = $description;
            $this->getHookNameValidator()($hook->name);
            if (!$hook->save()) {
                throw new \Exception('Failed to save Hook : ' . $hook->validateFields(false, true));
            }
        } catch (\Exception $e) {
            $this->io->getErrorStyle()->error($e->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * Get Hook name question with validation
     *
     * @return Question
     */
    protected function getHookNameQuestion(): Question
    {
        $hookNameQuestion = new Question('<question>Give me the name for your new HOOK</question>');
        $hookNameQuestion->setValidator($this->getHookNameValidator());
        $hookNameQuestion->setMaxAttempts(5);

        return $hookNameQuestion;
    }

    /**
     * @return \Closure
     */
    private function getHookNameValidator(): \Closure
    {
        return function ($answer) {
            if (!Validate::isHookName($answer) || preg_match('#^hook#i', $answer)) {
                throw new \RuntimeException('The hook name is invalid, it should match the pattern /^[a-zA-Z0-9_-]+$/ and can\'t start with "hook"');
            }

            return $answer;
        };
    }
}
