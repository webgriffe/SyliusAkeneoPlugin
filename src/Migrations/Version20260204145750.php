<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260204145750 extends AbstractMigration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Create webgriffe_sylius_akeneo_plugin_item_import_result table to store the result of the import of an item from Akeneo';
    }

    #[\Override]
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('webgriffe_sylius_akeneo_plugin_item_import_result')) {
            return;
        }
        $this->addSql('CREATE TABLE webgriffe_sylius_akeneo_plugin_item_import_result (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, akeneo_entity VARCHAR(255) NOT NULL, akeneo_identifier VARCHAR(255) NOT NULL, successful TINYINT(1) NOT NULL, message LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE webgriffe_sylius_akeneo_plugin_item_import_result');
    }
}
