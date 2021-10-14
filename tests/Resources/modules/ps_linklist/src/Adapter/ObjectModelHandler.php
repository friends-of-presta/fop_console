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

namespace PrestaShop\Module\LinkList\Adapter;

use PrestaShop\Module\LinkList\Model\LinkBlock;
use PrestaShop\PrestaShop\Adapter\Domain\AbstractObjectModelHandler;

class ObjectModelHandler extends AbstractObjectModelHandler
{
    /**
     * @param int $linkBlockId
     * @param array $associatedShops
     * @param bool $forceAssociate
     */
    public function handleMultiShopAssociation(
        int $linkBlockId,
        array $associatedShops,
        bool $forceAssociate = false
    ): void {
        $objectModel = new LinkBlock($linkBlockId);

        /*
         * Why we want to force association?
         * It's easier to work on multi-store tables even when feature is disabled
         * This way we can force association to store as legacy ObjectModel does
         * We need to remember that multi-store is always there, shop tables are always there
         *
         * @todo: this should be part of AbstractObjectModelHandler
         */
        if ($forceAssociate) {
            $objectModel->associateTo($associatedShops);

            return;
        }

        $this->associateWithShops($objectModel, $associatedShops);
    }
}
