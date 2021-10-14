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

/**
 * Class CategoryChoiceProvider.
 */
final class CategoryChoiceProvider extends AbstractDatabaseChoiceProvider
{
    /**
     * @return array
     */
    public function getChoices()
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('cc.id_category, ccl.name')
            ->from($this->dbPrefix . 'category', 'cc')
            ->innerJoin('cc', $this->dbPrefix . 'category_lang', 'ccl', 'cc.id_category = ccl.id_category')
            ->innerJoin('cc', $this->dbPrefix . 'category_shop', 'ccs', 'cc.id_category = ccs.id_category')
            ->andWhere('cc.active = 1')
            ->andWhere('ccl.id_lang = :idLang')
            ->andWhere('ccs.id_shop IN (:shopIds)')
            ->setParameter('idLang', $this->idLang)
            ->setParameter('shopIds', implode(',', $this->shopIds))
            ->orderBy('ccl.name')
        ;

        $categories = $qb->execute()->fetchAll();
        $choices = [];
        foreach ($categories as $category) {
            $choices[$category['name']] = $category['id_category'];
        }

        return $choices;
    }
}
