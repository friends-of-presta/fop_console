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

namespace PrestaShop\Module\LinkList\Presenter;

use Meta;
use PrestaShop\Module\LinkList\Model\LinkBlock;
use Tools;

/**
 * Class LinkBlockPresenter.
 */
class LinkBlockPresenter
{
    private $link;
    private $language;

    /**
     * LinkBlockPresenter constructor.
     *
     * @param \Link $link
     * @param \Language $language
     */
    public function __construct(\Link $link, \Language $language)
    {
        $this->link = $link;
        $this->language = $language;
    }

    /**
     * @param LinkBlock $cmsBlock
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function present(LinkBlock $cmsBlock)
    {
        return [
            'id' => (int) $cmsBlock->id,
            'title' => $cmsBlock->name[(int) $this->language->id],
            'hook' => (new \Hook((int) $cmsBlock->id_hook))->name,
            'position' => $cmsBlock->position,
            'links' => $this->makeLinks($cmsBlock->content, $cmsBlock->custom_content),
        ];
    }

    /**
     * Check the url if is an external link.
     *
     * @param string $url
     *
     * @return bool
     */
    public function isExternalLink($url)
    {
        $baseLink = preg_replace('#^(http)s?://#', '', $this->link->getBaseLink());
        $url = Tools::strtolower($url);

        if (preg_match('#^(http)s?://#', $url) && !preg_match('#^(http)s?://' . preg_quote(rtrim($baseLink, '/'), '/') . '#', $url)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $content
     * @param array $custom_content
     *
     * @return array
     */
    private function makeLinks($content, $custom_content)
    {
        $cmsLinks = $productLinks = $staticsLinks = $customLinks = $categoryLinks = [];

        if (isset($content['cms'])) {
            $cmsLinks = $this->makeCmsLinks($content['cms']);
        }

        if (isset($content['product'])) {
            $productLinks = $this->makeProductLinks($content['product']);
        }

        if (isset($content['static'])) {
            $staticsLinks = $this->makeStaticLinks($content['static']);
        }

        if (isset($content['category'])) {
            $categoryLinks = $this->makeCategoryLinks($content['category']);
        }

        $customLinks = $this->makeCustomLinks($custom_content);

        return array_merge(
            $cmsLinks,
            $productLinks,
            $staticsLinks,
            $customLinks,
            $categoryLinks
        );
    }

    /**
     * @param array $cmsIds
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function makeCmsLinks($cmsIds)
    {
        $cmsLinks = [];
        foreach ($cmsIds as $cmsId) {
            $cms = new \CMS((int) $cmsId);
            if (null !== $cms->id && $cms->active) {
                $cmsLinks[] = [
                    'id' => 'link-cms-page-' . $cms->id,
                    'class' => 'cms-page-link',
                    'title' => $cms->meta_title[(int) $this->language->id],
                    'description' => $cms->meta_description[(int) $this->language->id],
                    'url' => $this->link->getCMSLink($cms),
                ];
            }
        }

        return $cmsLinks;
    }

    /**
     * @param array $productIds
     *
     * @return array
     */
    private function makeProductLinks($productIds)
    {
        $productLinks = [];
        foreach ($productIds as $productId) {
            if (false === $productId) {
                continue;
            }

            $meta = \Meta::getMetaByPage($productId, (int) $this->language->id);
            $productLinks[] = [
                'id' => 'link-product-page-' . $productId,
                'class' => 'cms-page-link',
                'title' => $meta['title'],
                'description' => $meta['description'],
                'url' => $this->link->getPageLink($productId, true),
            ];
        }

        return $productLinks;
    }

    /**
     * @param array $staticIds
     *
     * @return array
     */
    private function makeStaticLinks($staticIds)
    {
        $staticLinks = [];
        foreach ($staticIds as $staticId) {
            if (false === $staticId) {
                continue;
            }

            $meta = \Meta::getMetaByPage($staticId, (int) $this->language->id);
            $staticLinks[] = [
                'id' => 'link-static-page-' . $staticId,
                'class' => 'cms-page-link',
                'title' => $meta['title'],
                'description' => $meta['description'],
                'url' => $this->link->getPageLink($staticId, true),
            ];
        }

        return $staticLinks;
    }

    /**
     * @param array $customContent
     *
     * @return array
     */
    private function makeCustomLinks($customContent)
    {
        $customLinks = [];

        if (!isset($customContent[$this->language->id])) {
            return $customLinks;
        }

        $customLinks = $customContent[$this->language->id];

        $self = $this;
        $customLinks = array_map(function ($el) use ($self) {
            return [
                'id' => 'link-custom-page-' . Tools::link_rewrite($el['title']),
                'class' => 'custom-page-link',
                'title' => $el['title'],
                'description' => '',
                'url' => $el['url'],
                'target' => $self->isExternalLink($el['url']) ? '_blank' : '',
            ];
        }, array_filter($customLinks));

        return $customLinks;
    }

    /**
     * @param array $categoryIds
     *
     * @return array
     */
    private function makeCategoryLinks($categoryIds)
    {
        $categoryLinks = [];
        foreach ($categoryIds as $categoryId) {
            if (false === $categoryId) {
                continue;
            }

            $meta = Meta::getCategoryMetas($categoryId, (int) $this->language->id, '', null);
            $categoryLinks[] = [
                'id' => 'link-category-' . $categoryId,
                'class' => 'category-link',
                'title' => $meta['name'],
                'description' => strip_tags($meta['description']),
                'url' => $this->link->getCategoryLink((int) $categoryId),
            ];
        }

        return $categoryLinks;
    }
}
