<?php declare(strict_types=1);

namespace Myfav\Acl\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1720566194MyfavCategoryAllowCustomerGroup extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1720556194;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `myfav_category_allow_customer_group` (
                `category_id` BINARY(16) NOT NULL,
                `category_version_id` BINARY(16) NOT NULL,
                `customer_group_id` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) DEFAULT NULL,
                PRIMARY KEY (`category_id`,`customer_group_id`),
                KEY `fk.mcacg.category_id` (`category_id`,`category_version_id`),
                KEY `fk.mcacg.customer_group_id` (`customer_group_id`),
                CONSTRAINT `fk.mcacg.category_id` FOREIGN KEY (`category_id`,`category_version_id`) REFERENCES `category` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.mcacg.customer_group_id` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($query);

        if (!$this->columnExists($connection, 'category', 'myfavAclAllowCustomerGroup')) {
            $this->updateInheritance($connection, 'category', 'myfavAclAllowCustomerGroup');
        }
        if (!$this->columnExists($connection, 'customer_group', 'myfavAclAllowCategory')) {
            $this->updateInheritance($connection, 'customer_group', 'myfavAclAllowCategory');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
