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

namespace PrestaShop\Module\LinkList;

use Context;
use Db;
use Hook;
use Language;
use PrestaShop\Module\LinkList\Model\LinkBlock;
use Shop;
use Symfony\Component\Translation\TranslatorInterface as Translator;

/**
 * Class LegacyLinkBlockRepository.
 */
class LegacyLinkBlockRepository
{
    /**
     * @var Db
     */
    private $db;

    /**
     * @var Shop
     */
    private $shop;

    /**
     * @var string
     */
    private $db_prefix;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @param Db $db
     * @param Shop $shop
     * @param Translator $translator
     */
    public function __construct(Db $db, Shop $shop, Translator $translator)
    {
        $this->db = $db;
        $this->shop = $shop;
        $this->db_prefix = $db->getPrefix();
        $this->translator = $translator;
    }

    /**
     * @param int $id_hook
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function getByIdHook($id_hook)
    {
        $id_hook = (int) $id_hook;

        $sql = "SELECT lb.`id_link_block`
                    FROM {$this->db_prefix}link_block lb
                    INNER JOIN {$this->db_prefix}link_block_shop lbs ON lbs.`id_link_block` = lb.`id_link_block`
                    WHERE lb. `id_hook` = $id_hook AND lbs.`id_shop` = {$this->shop->id}
                    ORDER by lbs.`position`
                ";
        $ids = $this->db->executeS($sql);

        $cmsBlock = [];
        foreach ($ids as $id) {
            $cmsBlock[] = new LinkBlock((int) $id['id_link_block']);
        }

        return $cmsBlock;
    }

    /**
     * @return bool
     */
    public function createTables()
    {
        $engine = _MYSQL_ENGINE_;
        $success = true;
        $this->dropTables();
        $queries = [
            "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}link_block`(
    			`id_link_block` int(10) unsigned NOT NULL auto_increment,
    			`id_hook` int(1) unsigned DEFAULT NULL,
    			`position` int(10) unsigned NOT NULL default '0',
    			`content` text default NULL,
    			PRIMARY KEY (`id_link_block`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}link_block_lang`(
    			`id_link_block` int(10) unsigned NOT NULL,
    			`id_lang` int(10) unsigned NOT NULL,
    			`name` varchar(40) NOT NULL default '',
    			`custom_content` text default NULL,
    			PRIMARY KEY (`id_link_block`, `id_lang`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}link_block_shop` (
    			`id_link_block` int(10) unsigned NOT NULL auto_increment,
    			`id_shop` int(10) unsigned NOT NULL,
                `position` int(10) unsigned NOT NULL default '0',
    			PRIMARY KEY (`id_link_block`, `id_shop`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
        ];
        foreach ($queries as $query) {
            $success &= $this->db->execute($query);
        }

        return (bool) $success;
    }

    public function dropTables()
    {
        $sql = "DROP TABLE IF EXISTS
			`{$this->db_prefix}link_block`,
			`{$this->db_prefix}link_block_lang`,
			`{$this->db_prefix}link_block_shop`";

        return $this->db->execute($sql);
    }

    /**
     * @return bool
     */
    public function installFixtures()
    {
        $success = true;
        $id_hook = (int) Hook::getIdByName('displayFooter');
        $queries = [
            'INSERT INTO `' . $this->db_prefix . 'link_block` (`id_link_block`, `id_hook`, `position`, `content`) VALUES
                (1, ' . $id_hook . ', 0, \'{"cms":[false],"product":["prices-drop","new-products","best-sales"],"static":[false]}\'),
                (2, ' . $id_hook . ', 1, \'{"cms":["1","2","3","4","5"],"product":[false],"static":["contact","sitemap","stores"]}\');',
        ];
        foreach (Language::getLanguages(true, Context::getContext()->shop->id) as $lang) {
            $queries[] = 'INSERT INTO `' . $this->db_prefix . 'link_block_lang` (`id_link_block`, `id_lang`, `name`) VALUES
                (1, ' . (int) $lang['id_lang'] . ', "' . pSQL($this->translator->trans('Products', [], 'Modules.Linklist.Shop', $lang['locale'])) . '"),
                (2, ' . (int) $lang['id_lang'] . ', "' . pSQL($this->translator->trans('Our company', [], 'Modules.Linklist.Shop', $lang['locale'])) . '");'
            ;
        }

        foreach ($this->shop::getContextListShopID() as $shopId) {
            $queries[] = 'INSERT INTO `' . $this->db_prefix . 'link_block_shop` (`id_link_block`, `id_shop`, `position`) VALUES
                (1, ' . (int) $shopId . ', 0),
                (2, ' . (int) $shopId . ', 1);'
            ;
        }

        foreach ($queries as $query) {
            $success &= $this->db->execute($query);
        }

        return $success;
    }
}
