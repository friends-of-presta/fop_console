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

namespace PrestaShop\Module\LinkList\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Query\QueryBuilder;
use Hook;
use PrestaShop\Module\LinkList\Adapter\ObjectModelHandler;
use PrestaShop\PrestaShop\Adapter\Shop\Context;
use PrestaShop\PrestaShop\Core\Exception\DatabaseException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class LinkBlockRepository.
 */
class LinkBlockRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @var array
     */
    private $languages;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var bool
     */
    private $isMultiStoreUsed;

    /**
     * @var Context
     */
    private $multiStoreContext;

    /**
     * @var ObjectModelHandler
     */
    private $objectModelHandler;

    /**
     * LinkBlockRepository constructor.
     *
     * @param Connection $connection
     * @param string $dbPrefix
     * @param array $languages
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Connection $connection,
        $dbPrefix,
        array $languages,
        TranslatorInterface $translator,
        bool $isMultiStoreUsed,
        Context $multiStoreContext,
        ObjectModelHandler $objectModelHandler
    ) {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
        $this->languages = $languages;
        $this->translator = $translator;
        $this->isMultiStoreUsed = $isMultiStoreUsed;
        $this->objectModelHandler = $objectModelHandler;
        $this->multiStoreContext = $multiStoreContext;
    }

    /**
     * Returns the list of hook with associated Link blocks.
     *
     * @return array
     */
    public function getHooksWithLinks()
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select('h.id_hook, h.name, h.title')
            ->from($this->dbPrefix . 'link_block', 'lb')
            ->leftJoin('lb', $this->dbPrefix . 'hook', 'h', 'lb.id_hook = h.id_hook')
            ->groupBy('h.id_hook')
            ->orderBy('h.name')
        ;

        return $qb->execute()->fetchAll();
    }

    /**
     * @param array $data
     *
     * @return string
     *
     * @throws DatabaseException
     */
    public function create(array $data)
    {
        $idHook = $data['id_hook'];

        $qb = $this->connection->createQueryBuilder();
        $qb
            ->insert($this->dbPrefix . 'link_block')
            ->values([
                'id_hook' => ':idHook',
                'content' => ':content',
            ])
            ->setParameters([
                'idHook' => $idHook,
                'content' => json_encode([
                    'cms' => empty($data['cms']) ? [false] : $data['cms'],
                    'static' => empty($data['static']) ? [false] : $data['static'],
                    'product' => empty($data['product']) ? [false] : $data['product'],
                    'category' => empty($data['category']) ? [false] : $data['category'],
                ]),
            ]);

        $this->executeQueryBuilder($qb, 'Link block error');
        $linkBlockId = $this->connection->lastInsertId();

        $this->updateLanguages((int) $linkBlockId, $data['block_name'], $data['custom_content']);

        $this->objectModelHandler->handleMultiShopAssociation(
            (int) $linkBlockId,
            $data['shop_association'],
            !$this->isMultiStoreUsed
        );

        $this->updateMaxPosition((int) $linkBlockId, $idHook, $data['shop_association']);

        return $linkBlockId;
    }

    /**
     * @param int $linkBlockId
     * @param array $data
     *
     * @throws DatabaseException
     */
    public function update($linkBlockId, array $data)
    {
        $qb = $this->connection->createQueryBuilder();
        $qb
            ->update($this->dbPrefix . 'link_block', 'lb')
            ->andWhere('lb.id_link_block = :linkBlockId')
            ->set('id_hook', ':idHook')
            ->set('content', ':content')
            ->setParameters([
                'linkBlockId' => $linkBlockId,
                'idHook' => $data['id_hook'],
                'content' => json_encode([
                    'cms' => empty($data['cms']) ? [false] : $data['cms'],
                    'static' => empty($data['static']) ? [false] : $data['static'],
                    'product' => empty($data['product']) ? [false] : $data['product'],
                    'category' => empty($data['category']) ? [false] : $data['category'],
                ]),
            ])
        ;

        $this->executeQueryBuilder($qb, 'Link block error');

        $this->updateLanguages($linkBlockId, $data['block_name'], $data['custom_content']);

        if ($this->isMultiStoreUsed) {
            $this->objectModelHandler->handleMultiShopAssociation(
                $linkBlockId,
                $data['shop_association']
            );
        }
    }

    /**
     * @param int $idLinkBlock
     *
     * @throws DatabaseException
     */
    public function delete($idLinkBlock): void
    {
        if (count($this->multiStoreContext->getAllShopIds()) === count($this->multiStoreContext->getContextListShopID())) {
            $tableNames = [
                'link_block_lang',
                'link_block',
                'link_block_shop',
            ];

            foreach ($tableNames as $tableName) {
                $qb = $this->connection->createQueryBuilder();
                $qb
                    ->delete($this->dbPrefix . $tableName)
                    ->andWhere('id_link_block = :idLinkBlock')
                    ->setParameter('idLinkBlock', $idLinkBlock)
                ;
                $this->executeQueryBuilder($qb, 'Delete error');
            }
        } else {
            // Delete only from specific stores
            if (!$this->multiStoreContext->isAllShopContext()) {
                $qb = $this->connection->createQueryBuilder();
                $qb
                    ->delete($this->dbPrefix . 'link_block_shop')
                    ->andWhere('id_link_block = :idLinkBlock')
                    ->andWhere('id_shop IN (:shopIds)')
                    ->setParameter('shopIds', $this->multiStoreContext->getContextListShopID(), Connection::PARAM_STR_ARRAY)
                    ->setParameter('idLinkBlock', $idLinkBlock);

                $this->executeQueryBuilder($qb, 'Delete from multi-store tables error');
            }
        }
    }

    /**
     * @return array
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createTables()
    {
        $errors = [];
        $engine = _MYSQL_ENGINE_;
        $this->dropTables();

        $queries = [
            "CREATE TABLE IF NOT EXISTS `{$this->dbPrefix}link_block`(
    			`id_link_block` int(10) unsigned NOT NULL auto_increment,
    			`id_hook` int(1) unsigned DEFAULT NULL,
                `position` int(10) unsigned NOT NULL default '0',
    			`content` text default NULL,
    			PRIMARY KEY (`id_link_block`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$this->dbPrefix}link_block_lang`(
    			`id_link_block` int(10) unsigned NOT NULL,
    			`id_lang` int(10) unsigned NOT NULL,
    			`name` varchar(40) NOT NULL default '',
    			`custom_content` text default NULL,
    			PRIMARY KEY (`id_link_block`, `id_lang`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$this->dbPrefix}link_block_shop` (
    			`id_link_block` int(10) unsigned NOT NULL auto_increment,
                `id_shop` int(10) unsigned NOT NULL,
                `position` int(10) unsigned NOT NULL default '0',
    			PRIMARY KEY (`id_link_block`, `id_shop`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
        ];

        foreach ($queries as $query) {
            $statement = $this->connection->executeQuery($query);

            if ($statement instanceof Statement && 0 != (int) $statement->errorCode()) {
                $errors[] = [
                    'key' => json_encode($statement->errorInfo()),
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        return $errors;
    }

    /**
     * @return array
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function installFixtures()
    {
        $errors = [];
        $id_hook = (int) Hook::getIdByName('displayFooter');

        $queries = [
            'INSERT INTO `' . $this->dbPrefix . 'link_block` (`id_link_block`, `id_hook`, `position`, `content`) VALUES
                (1, ' . $id_hook . ', 0, \'{"cms":[false],"product":["prices-drop","new-products","best-sales"],"static":[false],"category":[false]}\'),
                (2, ' . $id_hook . ', 1, \'{"cms":["1","2","3","4","5"],"product":[false],"static":["contact","sitemap","stores"],"category":[false]}\');',
        ];

        foreach ($this->languages as $lang) {
            $queries[] = 'INSERT INTO `' . $this->dbPrefix . 'link_block_lang` (`id_link_block`, `id_lang`, `name`) VALUES
                (1, ' . (int) $lang['id_lang'] . ', "' . pSQL($this->translator->trans('Products', [], 'Modules.Linklist.Shop', $lang['locale'])) . '"),
                (2, ' . (int) $lang['id_lang'] . ', "' . pSQL($this->translator->trans('Our company', [], 'Modules.Linklist.Shop', $lang['locale'])) . '");'
            ;
        }

        foreach ($this->multiStoreContext->getShops(true, true) as $shopId) {
            $queries[] = 'INSERT INTO `' . $this->dbPrefix . 'link_block_shop` (`id_link_block`, `id_shop`, `position`) VALUES
                (1, ' . (int) $shopId . ', 0),
                (2, ' . (int) $shopId . ', 1);'
            ;
        }

        foreach ($queries as $query) {
            $statement = $this->connection->executeQuery($query);
            if ($statement instanceof Statement && 0 != (int) $statement->errorCode()) {
                $errors[] = [
                    'key' => json_encode($statement->errorInfo()),
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        return $errors;
    }

    /**
     * @return array
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function dropTables()
    {
        $errors = [];
        $tableNames = [
            'link_block_shop',
            'link_block_lang',
            'link_block',
        ];
        foreach ($tableNames as $tableName) {
            $sql = 'DROP TABLE IF EXISTS ' . $this->dbPrefix . $tableName;
            $statement = $this->connection->executeQuery($sql);
            if ($statement instanceof Statement && 0 != (int) $statement->errorCode()) {
                $errors[] = [
                    'key' => json_encode($statement->errorInfo()),
                    'parameters' => [],
                    'domain' => 'Admin.Modules.Notification',
                ];
            }
        }

        return $errors;
    }

    /**
     * @param int $linkBlockId
     * @param array $blockName
     * @param array $custom
     *
     * @throws DatabaseException
     */
    private function updateLanguages($linkBlockId, array $blockName, array $custom)
    {
        foreach ($this->languages as $language) {
            $qb = $this->connection->createQueryBuilder();
            $qb
                ->select('lbl.id_link_block')
                ->from($this->dbPrefix . 'link_block_lang', 'lbl')
                ->andWhere('lbl.id_link_block = :linkBlockId')
                ->andWhere('lbl.id_lang = :langId')
                ->setParameter('linkBlockId', $linkBlockId)
                ->setParameter('langId', $language['id_lang'])
            ;
            $foundRows = $qb->execute()->rowCount();

            $qb = $this->connection->createQueryBuilder();
            if (!$foundRows) {
                $qb
                    ->insert($this->dbPrefix . 'link_block_lang')
                    ->values([
                        'id_link_block' => ':linkBlockId',
                        'id_lang' => ':langId',
                        'name' => ':name',
                        'custom_content' => ':customContent',
                    ])
                ;
            } else {
                $qb
                    ->update($this->dbPrefix . 'link_block_lang', 'lbl')
                    ->set('name', ':name')
                    ->set('custom_content', ':customContent')
                    ->andWhere('lbl.id_link_block = :linkBlockId')
                    ->andWhere('lbl.id_lang = :langId')
                ;
            }

            $qb
                ->setParameters([
                    'linkBlockId' => $linkBlockId,
                    'langId' => $language['id_lang'],
                    'name' => $blockName[$language['id_lang']],
                    'customContent' => empty($custom) ? null : json_encode($custom[$language['id_lang']]),
                ]);

            $this->executeQueryBuilder($qb, 'Link block language error');
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string $errorPrefix
     *
     * @return Statement|int
     *
     * @throws DatabaseException
     */
    private function executeQueryBuilder(QueryBuilder $qb, $errorPrefix = 'SQL error')
    {
        $statement = $qb->execute();
        if ($statement instanceof Statement && !empty($statement->errorInfo())) {
            throw new DatabaseException($errorPrefix . ': ' . var_export($statement->errorInfo(), true));
        }

        return $statement;
    }

    /**
     * @param int $idHook
     * @param int $idShop
     *
     * @return int
     */
    private function getHookMaxPosition(int $idHook, int $idShop): int
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('MAX(lbs.position)')
            ->from($this->dbPrefix . 'link_block_shop', 'lbs')
            ->leftJoin('lbs', $this->dbPrefix . 'link_block', 'lb', 'lbs.id_link_block = lb.id_link_block')
            ->andWhere('lb.id_hook = :idHook')
            ->andWhere('lbs.id_shop = :idShop')
            ->setParameter('idHook', $idHook)
            ->setParameter('idShop', $idShop)
        ;

        $maxPosition = $qb->execute()->fetchColumn(0);

        return null !== $maxPosition ? $maxPosition + 1 : 0;
    }

    /**
     * @param int $linkBlockId
     * @param array $shopIds
     *
     * @throws DatabaseException
     */
    private function updateMaxPosition(int $linkBlockId, ?int $hookId = null, array $shopIds): void
    {
        $qb = $this->connection->createQueryBuilder();
        foreach ($shopIds as $shopId) {
            $qb
                ->update($this->dbPrefix . 'link_block_shop lbs')
                ->set('position', ':position')
                ->andWhere('lbs.id_shop = :shopId')
                ->andWhere('lbs.id_link_block = :linkBlockId')
                ->setParameter('position', $this->getHookMaxPosition($hookId, $shopId))
                ->setParameter('shopId', $shopId)
                ->setParameter('linkBlockId', $linkBlockId);

            $this->executeQueryBuilder($qb, 'Link block max position update error');
        }
    }

    /**
     * @param int $shopId
     * @param array $positionsData
     *
     * @return void
     */
    public function updatePositions(int $shopId, array $positionsData = []): void
    {
        try {
            $this->connection->beginTransaction();

            $i = 0;
            foreach ($positionsData['positions'] as $position) {
                $qb = $this->connection->createQueryBuilder();
                $qb
                    ->update($this->dbPrefix . 'link_block_shop')
                    ->set('position', ':position')
                    ->andWhere('id_link_block = :linkBlockId')
                    ->andWhere('id_shop = :shopId')
                    ->setParameter('shopId', $shopId)
                    ->setParameter('linkBlockId', $position['rowId'])
                    ->setParameter('position', $i);

                ++$i;

                $statement = $qb->execute();
                if ($statement instanceof Statement && $statement->errorCode()) {
                    throw new DatabaseException('Could not update #%i');
                }
            }
            $this->connection->commit();
        } catch (ConnectionException $e) {
            $this->connection->rollBack();

            throw new DatabaseException('Could not update positions.');
        }
    }
}
