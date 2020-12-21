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
 * @copyright 2019-present Friends of Presta community
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace FOP\Console\Commands;

use FOP\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

final class AddHook extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('fop:add-hook')
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
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $name = $input->getOption('name') ?? $helper->ask($input, $output, new Question('<question>Give me the name for your new HOOK</question>'));
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
            if (!$hook->save()) {
                throw new \Exception('Failed to save Hook : ' . $hook->validateFields(false, true));
            }
        } catch (\Exception $e) {
            $io->getErrorStyle()->error($e->getMessage());

            return 1;
        }

        return 0;
    }
}
