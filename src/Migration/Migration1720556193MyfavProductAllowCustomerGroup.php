<?php declare(strict_types=1);

namespace Myfav\Acl\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1720556193MyfavProductAllowCustomerGroup extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1720556193;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `myfav_product_allow_customer_group` (
                `product_id` BINARY(16) NOT NULL,
                `product_version_id` BINARY(16) NOT NULL,
                `customer_group_id` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) DEFAULT NULL,
                PRIMARY KEY (`product_id`,`customer_group_id`),
                KEY `fk.mpacg.product_id` (`product_id`,`product_version_id`),
                KEY `fk.mpacg.customer_group_id` (`customer_group_id`),
                CONSTRAINT `fk.mpacg.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.mpacg.customer_group_id` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->executeStatement($query);

        if (!$this->columnExists($connection, 'product', 'myfavAclAllowCustomerGroup')) {
            $this->updateInheritance($connection, 'product', 'myfavAclAllowCustomerGroup');
        }
        if (!$this->columnExists($connection, 'customer_group', 'myfavAclAllowProduct')) {
            $this->updateInheritance($connection, 'customer_group', 'myfavAclAllowProduct');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
