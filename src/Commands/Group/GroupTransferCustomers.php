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

namespace FOP\Console\Commands\Group;

use Customer;
use FOP\Console\Command;
use Group;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Validate;

/**
 * This command transfers or add customers from one group to an other.
 */
final class GroupTransferCustomers extends Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;
    public const ABORTED = 3;

    public const ACTION_DELETE_FROM_GROUP = 1;
    public const ACTION_REMOVE_CUSTOMERS_TO_FROM_GROUP = 2;
    public const ACTION_JUST_COPY = 3;
    public const ACTION_CANCEL = 4;

    public $groupQuestionsKeyPrefix = 'category_';
    public $actionQuestionsKeyPrefix = 'action_';
    public $displaySummaryTab = true;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('fop:group:transfer-customers')
            ->setAliases(['fop:customer-groups'])
            ->setDescription('Transfer or add customers from one group to an other.')
            ->setHelp('Transfer or add customers from one group to an other.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $optionsGroupFrom = $this->getQuestionsOptions('optionsGroupFrom');
        $optionsGroupTo = $this->getQuestionsOptions('optionsGroupTo');

        if (count($optionsGroupFrom) <= 1) {
            $output->writeln('<error>command aborted : at least 2 groups and 1 customer are required.</error>');

            return self::FAILURE;
        }

        // Step 1 : Group From Question
        $prefix = $this->groupQuestionsKeyPrefix;
        $label = 'Please select the group from :';

        $groupFromId = $this->generateChoiceQuestion($input, $output, $prefix, $label, $optionsGroupFrom, 'groups');

        // Remove selected group from in group to list.
        unset($optionsGroupTo[$this->groupQuestionsKeyPrefix . $groupFromId]);

        // Step 2 : Group To Question
        $label = 'Please select the group to :';
        $groupToId = $this->generateChoiceQuestion($input, $output, $prefix, $label, $optionsGroupTo, 'groups');
        $groupFromName = $this->getGroupName($groupFromId);
        $optionsActions = $this->getQuestionsOptions('optionsActions', $groupFromName);

        // Remove delete action when from group as a PS defautl group
        if (in_array((int) $groupFromId, $this->getDefaultPsGroups())) {
            unset($optionsActions[$this->actionQuestionsKeyPrefix . self::ACTION_DELETE_FROM_GROUP]);
        }

        // Step 3 : Action after Question
        $prefix = $this->actionQuestionsKeyPrefix;
        $groupToName = $this->getGroupName($groupToId);
        $label = 'After moving the customers from (' . $groupFromName . ') group to (' . $groupToName . ') group ';
        $selectedActionId = $this->generateChoiceQuestion($input, $output, $prefix, $label, $optionsActions, 'actions');

        if (self::ACTION_CANCEL === $selectedActionId) {
            $output->writeln('<info>Action canceled</info>');

            return self::ABORTED;
        }

        // Step 4 : user confirmation Question
        if (!$this->userConfirmation(
            $input,
            $output,
            $groupFromName,
            $groupToName,
            $optionsActions[$prefix . $selectedActionId]
        )) {
            $output->writeln('<info>Action abandoned</info>');

            return self::ABORTED;
        }

        $this->groupCustomersTransfer($groupFromId, $groupToId, $selectedActionId, $output);

        if ($this->displaySummaryTab) {
            $this->io->title('Customers groups');
            $this->io->table(
                ['ID', 'Category name', 'Members Nb', 'Reduction (%)'],
                $this->formatGroupsInformations(
                    Group::getGroups($this->getDefautlLang(), false), // refresh groups to avoid old datas.
                    'table'
                )
            );
        }

        return self::SUCCESS;
    }

    /**
     * @param int $idGroupFrom
     * @param int $IdGroupTo
     * @param int $actionAfter
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws \Exception
     */
    private function groupCustomersTransfer(
        int $idGroupFrom,
        int $IdGroupTo,
        int $actionAfter,
        OutputInterface $output
    ): int {
        $groupFrom = new Group($idGroupFrom);
        $GroupTo = new Group($IdGroupTo);
        $hasError = 0;

        if (!Validate::isLoadedObject($groupFrom)
         || !Validate::isLoadedObject($GroupTo)) {
            $output->writeln('<error>Invalid groups given </error>');

            return self::FAILURE;
        }

        $groupFromCustomers = $groupFrom->getCustomers();

        $progress = new ProgressBar($output, count($groupFromCustomers));
        $progress->start();

        foreach ($groupFromCustomers as $k => $v) {
            $customer = new Customer($v['id_customer']);
            $customerGroups = $customer->getGroups();
            $progress->advance();

            switch ((int) $actionAfter) {
                case self::ACTION_JUST_COPY:
                    $customer->addGroups([$GroupTo->id]);
                    continue 2;
                case self::ACTION_REMOVE_CUSTOMERS_TO_FROM_GROUP:
                    $customerGroups = array_diff($customerGroups, [$groupFrom->id]); // remove group from
                    break;
            }

            array_push($customerGroups, $GroupTo->id); // add group to

            if ((int) $groupFrom->id == (int) $customer->id_default_group) {
                $customer->id_default_group = (int) $GroupTo->id;
            }

            try {
                $customer->updateGroup($customerGroups);
                $customer->update();
            } catch (\Exception $e) {
                $output->writeln(
                    '<error>
                            An error has occurred when try to update customer ' . $customer->email . ' to group ' . $this->getGroupName($groupFrom->id) . ' : ' . $e->getMessage() .
                        '</error>'
                );
                ++$hasError;

                return self::FAILURE;
            }
        }

        // Delete the form group
        if (self::ACTION_DELETE_FROM_GROUP == (int) $actionAfter && 0 == $hasError) {
            try {
                $groupFrom->delete();
            } catch (\Exception $e) {
                $output->writeln(
                    '<error>
                        An error has occurred when try to delete group ' . $this->getGroupName($groupFrom->id) . ' : ' . $e->getMessage() .
                    '</error>'
                );

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param array $groups groups datas
     * @param string $type
     * @param bool $skipEmpty
     *
     * @return array
     */
    private function formatGroupsInformations(array $groups, $type = 'table', $skipEmpty = false): array
    {
        $groupsInformations = [];

        foreach ($groups as $group) {
            $groupObject = new Group((int) $group['id_group']);
            $nb = $groupObject->getCustomers(true);

            if ($nb <= 0 && $skipEmpty) {
                continue;
            }

            if ($type === 'table') {
                $groupsInformations[] = [
                    $groupObject->id,
                    $group['name'],
                    $nb,
                    $groupObject->reduction . ' %',
                ];
            } elseif ($type === 'question') {
                $groupsInformations[$this->groupQuestionsKeyPrefix . $groupObject->id] = $group['name'] . ' (' . $nb . ')';
            }
        }

        return $groupsInformations;
    }

    /**
     * @param int $idGroup
     *
     * @return string
     */
    private function getGroupName(int $idGroup): string
    {
        return (new Group($idGroup, $this->getDefautlLang()))->name;
    }

    /**
     * @param string $type
     * @param string $groupFromName
     *
     * @return array
     */
    private function getQuestionsOptions(string $type, string $groupFromName = null): array
    {
        $questionsOptions = [
            'optionsGroupFrom' => $this->formatGroupsInformations($this->getGroups(), 'question', true),
            'optionsGroupTo' => $this->formatGroupsInformations($this->getGroups(), 'question', false),
            'optionsActions' => [
                $this->actionQuestionsKeyPrefix . self::ACTION_DELETE_FROM_GROUP => 'Delete (' . $groupFromName . ') group',
                $this->actionQuestionsKeyPrefix . self::ACTION_REMOVE_CUSTOMERS_TO_FROM_GROUP => 'Remove customers in (' . $groupFromName . ') group',
                $this->actionQuestionsKeyPrefix . self::ACTION_JUST_COPY => 'Just add to new group',
                $this->actionQuestionsKeyPrefix . self::ACTION_CANCEL => 'Cancel current action',
            ],
        ];

        return $questionsOptions[$type] ?? [];
    }

    /**
     * user confirmation
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $groupFromName
     * @param string $groupToName
     * @param string $selectedActionValue
     *
     * @return bool
     */
    private function userConfirmation(
        InputInterface $input,
        OutputInterface $output,
        string $groupFromName,
        string $groupToName,
        string $selectedActionValue
    ): bool {
        $helper = $this->getHelper('question');

        $questionConfirm = new ConfirmationQuestion(
            '<question>
                This action will move the customers from group (' . $groupFromName . ') into group (' . $groupToName . ') and
                ' . $selectedActionValue . '.
                Continue with this action? (Y/N) :
            </question>',
            false,
            '/^(y|Y)/i'
        );

        return $helper->ask($input, $output, $questionConfirm);
    }

    /**
     * Default Prestashop groups
     *
     * @return array
     */
    private function getDefaultPsGroups(): array
    {
        $config = $this
            ->getContainer()
            ->get('prestashop.adapter.legacy.configuration')
        ;

        return [
            $config->getInt('PS_UNIDENTIFIED_GROUP'),
            $config->getInt('PS_GUEST_GROUP'),
            $config->getInt('PS_CUSTOMER_GROUP'),
        ];
    }

    /**
     * get groups
     *
     * @return array
     */
    private function getGroups(): array
    {
        return $this
            ->getContainer()
            ->get('prestashop.adapter.data_provider.group')
            ->getGroups($this->getDefautlLang(), false)
        ;
    }

    /**
     * Defautl lang id
     *
     * @return int
     */
    private function getDefautlLang(): int
    {
        return (int) $this
            ->getContainer()
            ->get('prestashop.adapter.legacy.configuration')
            ->getInt('PS_LANG_DEFAULT')
        ;
    }

    /**
     * Defautl lang id
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $questionsKeyPrefix
     * @param string $questionLabel
     * @param array $questionOptions
     * @param string $type
     *
     * @return int
     *
     * @throws \UnexpectedValueException
     */
    private function generateChoiceQuestion(
        InputInterface $input,
        OutputInterface $output,
        string $questionsKeyPrefix,
        string $questionLabel,
        array $questionOptions,
        string $type
    ): int {
        $availableTypes = ['groups', 'actions'];

        if (!in_array($type, $availableTypes)) {
            throw new \UnexpectedValueException('The type must be.' . implode('or', $availableTypes));
        }

        $helper = $this->getHelper('question');

        $choiceQuestion = new ChoiceQuestion(
            '<question>' . $questionLabel . '</question>',
            $questionOptions
        );

        $choiceQuestion->setErrorMessage('Option %s not found.');

        $optionSeletedKey = $helper->ask($input, $output, $choiceQuestion);
        $optionSeletedID = (int) str_replace($questionsKeyPrefix, '', $optionSeletedKey);

        switch ($type) {
          case 'groups':
            $groupName = $this->getGroupName($optionSeletedID);
            $output->writeln('<info>You have just selected: ' . $groupName . '</info>');
            break;
          case 'actions':
            $output->writeln('<info>You have just selected: ' . $questionOptions[$optionSeletedKey] . '</info>');
            break;
        }

        return $optionSeletedID;
    }
}
