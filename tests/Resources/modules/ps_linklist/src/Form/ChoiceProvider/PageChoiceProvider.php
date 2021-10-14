<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\LinkList\Form\ChoiceProvider;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Foundation\Database\EntityNotFoundException;
use Tools;

/**
 * Class PageChoiceProvider.
 */
final class PageChoiceProvider extends AbstractDatabaseChoiceProvider
{
    /**
     * @var array
     */
    private $pageNames;

    /**
     * PageChoiceProvider constructor.
     *
     * @param Connection $connection
     * @param string $dbPrefix
     * @param int $idLang
     * @param array $shopIds
     * @param array $pageNames
     */
    public function __construct(
        Connection $connection,
        $dbPrefix,
        $idLang,
        array $shopIds,
        array $pageNames
    ) {
        parent::__construct($connection, $dbPrefix, $idLang, $shopIds);
        $this->pageNames = $pageNames;
    }

    /**
     * @return array
     *
     * @throws EntityNotFoundException
     */
    public function getChoices()
    {
        $choices = [];
        foreach ($this->pageNames as $pageName) {
            $qb = $this->connection->createQueryBuilder();
            $qb
                ->select('m.id_meta, ml.title')
                ->from($this->dbPrefix . 'meta', 'm')
                ->leftJoin('m', $this->dbPrefix . 'meta_lang', 'ml', 'm.id_meta = ml.id_meta')
                ->andWhere($qb->expr()->orX('m.page = :page', 'm.page = :pageSlug'))
                ->andWhere('ml.id_lang = :idLang')
                ->andWhere('ml.id_shop IN (:shopIds)')
                ->setParameter('idLang', $this->idLang)
                ->setParameter('shopIds', implode(',', $this->shopIds))
                ->setParameter('page', $pageName)
                ->setParameter('pageSlug', str_replace('-', '', Tools::strtolower($pageName)))
            ;
            $meta = $qb->execute()->fetchAll();
            if (!empty($meta)) {
                $choices[$meta[0]['title']] = $pageName;
            }
        }

        return $choices;
    }
}
