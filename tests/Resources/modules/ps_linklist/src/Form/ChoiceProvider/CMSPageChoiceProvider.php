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

/**
 * Class CMSPageChoiceProvider.
 */
final class CMSPageChoiceProvider extends AbstractDatabaseChoiceProvider
{
    /**
     * @var array
     */
    private $categories;

    /**
     * CMSPageChoiceProvider constructor.
     *
     * @param Connection $connection
     * @param string $dbPrefix
     * @param array $categories
     * @param int $idLang
     * @param array $shopIds
     */
    public function __construct(
        Connection $connection,
        $dbPrefix,
        array $categories,
        $idLang,
        $shopIds
    ) {
        parent::__construct($connection, $dbPrefix, $idLang, $shopIds);
        $this->categories = $categories;
    }

    /**
     * @return array
     */
    public function getChoices()
    {
        $choices = [];

        foreach ($this->categories as $categoryName => $categoryId) {
            $qb = $this->connection->createQueryBuilder();
            $qb
                ->select('c.id_cms, cl.meta_title')
                ->from($this->dbPrefix . 'cms', 'c')
                ->innerJoin('c', $this->dbPrefix . 'cms_lang', 'cl', 'c.id_cms = cl.id_cms')
                ->innerJoin('c', $this->dbPrefix . 'cms_shop', 'cs', 'c.id_cms = cs.id_cms')
                ->andWhere('c.active = 1')
                ->andWhere('cl.id_lang = :idLang')
                ->andWhere('cs.id_shop IN (:shopIds)')
                ->andWhere('c.id_cms_category = :idCmsCategory')
                ->setParameter('idCmsCategory', $categoryId)
                ->setParameter('idLang', $this->idLang)
                ->setParameter('shopIds', implode(',', $this->shopIds))
                ->orderBy('c.position')
            ;
            $pages = $qb->execute()->fetchAll();
            foreach ($pages as $page) {
                $choices[$categoryName][$page['id_cms'] . ' ' . $page['meta_title']] = $page['id_cms'];
            }
        }

        return $choices;
    }
}
