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

use FOP\Console\Commands\Images\GenerateAbstract;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateProduct extends GenerateAbstract
{
    /** @var string */
    const IMAGE_TYPE = 'products';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('images:generate:products')
            ->setDescription('Regenerate product thumbnails')
            ->addArgument(
                'format',
                InputArgument::OPTIONAL,
                'product images formats ',
                'all'
            )
            ->addOption('delete', 'd', InputOption::VALUE_OPTIONAL, 'Delete current thumbnails');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $formats = $input->getArgument('format');
        $delete = (bool)$input->getOption('delete');

        $io = new SymfonyStyle($input, $output);
        if (true !== $this->_regenerateThumbnails(self::IMAGE_TYPE, $delete, $formats)) {
            $io->error('Unable to generate thumbnails');
            return 1;
        }

        if (count($this->errors)) {
            $io->error('The generation generate the folowing errors');
            foreach ($this->errors as $error) {
                $io->error($error);
            }
        }

        $io->success('Thumbnails generated with success');
    }

}