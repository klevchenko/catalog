<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210528125824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE catalog (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE catalog_item (id INT AUTO_INCREMENT NOT NULL, catalog_id INT NOT NULL, number VARCHAR(255) DEFAULT NULL, code VARCHAR(255) DEFAULT NULL, price VARCHAR(255) DEFAULT NULL, INDEX IDX_6AAE9568CC3C66FC (catalog_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE catalog_item ADD CONSTRAINT FK_6AAE9568CC3C66FC FOREIGN KEY (catalog_id) REFERENCES catalog (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE catalog_item DROP FOREIGN KEY FK_6AAE9568CC3C66FC');
        $this->addSql('DROP TABLE catalog');
        $this->addSql('DROP TABLE catalog_item');
    }
}
