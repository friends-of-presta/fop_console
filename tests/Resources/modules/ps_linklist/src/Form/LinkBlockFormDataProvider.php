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

namespace PrestaShop\Module\LinkList\Form;

use Hook;
use Language;
use PrestaShop\Module\LinkList\Cache\LinkBlockCacheInterface;
use PrestaShop\Module\LinkList\Model\LinkBlock;
use PrestaShop\Module\LinkList\Repository\LinkBlockRepository;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\Shop\Context;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepository;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;
use Ps_Linklist;

/**
 * Class LinkBlockFormDataProvider.
 */
class LinkBlockFormDataProvider implements FormDataProviderInterface
{
    /**
     * @var int|null
     */
    private $idLinkBlock;

    /**
     * @var LinkBlockRepository
     */
    private $repository;

    /**
     * @var LinkBlockCacheInterface
     */
    private $cache;

    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    /**
     * @var array
     */
    private $languages;

    /**
     * @var Context
     */
    private $shopContext;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * LinkBlockFormDataProvider constructor.
     *
     * @param LinkBlockRepository $repository
     * @param LinkBlockCacheInterface $cache
     * @param ModuleRepository $moduleRepository
     * @param array $languages
     * @param Context $shopContext
     * @param Configuration $configuration
     */
    public function __construct(
        LinkBlockRepository $repository,
        LinkBlockCacheInterface $cache,
        ModuleRepository $moduleRepository,
        array $languages,
        Context $shopContext,
        Configuration $configuration
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->moduleRepository = $moduleRepository;
        $this->languages = $languages;
        $this->shopContext = $shopContext;
        $this->configuration = $configuration;
    }

    /**
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function getData()
    {
        if (null === $this->idLinkBlock) {
            return [
                'link_block' => [
                    'shop_association' => $this->shopContext->getContextListShopID(),
                ],
            ];
        }

        $linkBlock = new LinkBlock($this->idLinkBlock);

        $arrayLinkBlock = $linkBlock->toArray();

        //The form and the database model don't have the same data hierarchy
        //Transform array $custom[en][1][name] to $custom[1][en][name]
        $arrayCustom = [];
        foreach ($arrayLinkBlock['custom_content'] as $idLang => $customs) {
            if (!is_array($customs)) {
                continue;
            }

            foreach ($customs as $i => $custom) {
                $arrayCustom[$i][$idLang] = $custom;
            }
        }

        return ['link_block' => [
            'id_link_block' => $arrayLinkBlock['id'],
            'block_name' => $arrayLinkBlock['name'],
            'id_hook' => $arrayLinkBlock['id_hook'],
            'cms' => isset($arrayLinkBlock['content']['cms']) ? $arrayLinkBlock['content']['cms'] : [],
            'product' => isset($arrayLinkBlock['content']['product']) ? $arrayLinkBlock['content']['product'] : [],
            'static' => isset($arrayLinkBlock['content']['static']) ? $arrayLinkBlock['content']['static'] : [],
            'category' => isset($arrayLinkBlock['content']['category']) ? $arrayLinkBlock['content']['category'] : [],
            'custom' => $arrayCustom,
            'shop_association' => $arrayLinkBlock['shop_association'],
        ]];
    }

    /**
     * Make sure to fill empty multilang fields if value for default is available
     *
     * @param array $linkBlock
     *
     * @return array
     */
    public function prepareData(array $linkBlock): array
    {
        $defaultLanguageId = (int) $this->configuration->get('PS_LANG_DEFAULT');

        if (!empty($linkBlock['block_name'])) {
            foreach ($this->languages as $language) {
                if (empty($linkBlock['block_name'][$language['id_lang']])) {
                    $linkBlock['block_name'][$language['id_lang']] = $linkBlock['block_name'][$defaultLanguageId];
                }
            }
        }

        if (!empty($linkBlock['custom'])) {
            foreach ($linkBlock['custom'] as $key => $customLanguages) {
                if ($this->isEmptyCustom($customLanguages)) {
                    continue;
                }

                foreach ($customLanguages as $idLang => $custom) {
                    $linkBlock['custom'][$key][$idLang] = [
                        'title' => $custom['title'] ?? $customLanguages[$defaultLanguageId]['title'],
                        'url' => $custom['url'] ?? $customLanguages[$defaultLanguageId]['url'],
                    ];
                }
            }
        }

        return $linkBlock;
    }

    /**
     * @param array $data
     *
     * @return array
     *
     * @throws \PrestaShop\PrestaShop\Adapter\Entity\PrestaShopDatabaseException
     */
    public function setData(array $data)
    {
        $linkBlock = $this->prepareData($data['link_block']);

        $errors = $this->validateLinkBlock($linkBlock);
        if (!empty($errors)) {
            return $errors;
        }

        $customContent = [];
        if (!empty($linkBlock['custom'])) {
            foreach ($linkBlock['custom'] as $customLanguages) {
                if ($this->isEmptyCustom($customLanguages)) {
                    continue;
                }

                foreach ($customLanguages as $idLang => $custom) {
                    $customContent[$idLang][] = $custom;
                }
            }
        }
        $linkBlock['custom_content'] = $customContent;
        $linkBlock['id_shop'] = $this->shopContext->getContextShopID();

        if (empty($linkBlock['id_link_block'])) {
            $linkBlockId = $this->repository->create($linkBlock);
            $this->setIdLinkBlock((int) $linkBlockId);
        } else {
            $linkBlockId = $linkBlock['id_link_block'];
            $this->repository->update($linkBlockId, $linkBlock);
        }
        $this->updateHook($linkBlock['id_hook']);
        $this->cache->clearModuleCache();

        return [];
    }

    /**
     * @return int
     */
    public function getIdLinkBlock()
    {
        return $this->idLinkBlock;
    }

    /**
     * @param int $idLinkBlock
     *
     * @return LinkBlockFormDataProvider
     */
    public function setIdLinkBlock($idLinkBlock)
    {
        $this->idLinkBlock = $idLinkBlock;

        return $this;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function validateLinkBlock(array $data)
    {
        $errors = [];
        if (!isset($data['id_hook'])) {
            $errors[] = [
                'key' => 'Missing id_hook',
                'domain' => 'Admin.Catalog.Notification',
                'parameters' => [],
            ];
        }

        if (!isset($data['block_name'])) {
            $errors[] = [
                'key' => 'Missing block_name',
                'domain' => 'Admin.Catalog.Notification',
                'parameters' => [],
            ];
        } else {
            foreach ($this->languages as $language) {
                if (empty($data['block_name'][$language['id_lang']])) {
                    $errors[] = [
                        'key' => 'Missing block_name value for language %s',
                        'domain' => 'Admin.Catalog.Notification',
                        'parameters' => [$language['iso_code']],
                    ];
                }
            }
        }

        if (!isset($data['custom'])) {
            return $errors;
        }

        foreach ($data['custom'] as $customIndex => $custom) {
            if ($this->isEmptyCustom($custom)) {
                continue;
            }

            $defaultLanguageId = (int) $this->configuration->get('PS_LANG_DEFAULT');
            $fields = ['title', 'url'];
            foreach ($fields as $field) {
                if (empty($custom[$defaultLanguageId][$field])) {
                    $errors[] = [
                        'key' => 'Missing %s value in custom[%s] for language %s',
                        'domain' => 'Admin.Catalog.Notification',
                        'parameters' => [$field, $customIndex, Language::getIsoById($defaultLanguageId)],
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * @param array $custom
     *
     * @return bool
     */
    private function isEmptyCustom(array $custom)
    {
        $fields = ['title', 'url'];
        foreach ($custom as $langCustom) {
            foreach ($fields as $field) {
                if (!empty($langCustom[$field])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Register the selected hook to this module if it was not registered yet.
     *
     * @param int $hookId
     *
     * @throws \PrestaShopException
     */
    private function updateHook($hookId)
    {
        $hookName = Hook::getNameById($hookId);
        $module = $this->moduleRepository->getInstanceByName(Ps_Linklist::MODULE_NAME);
        if (!Hook::isModuleRegisteredOnHook($module, $hookName, $this->shopContext->getContextShopID())) {
            Hook::registerHook($module, $hookName);
        }
    }
}
