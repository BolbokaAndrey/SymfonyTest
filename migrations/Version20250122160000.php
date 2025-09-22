<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250122160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create dynamic property system tables';
    }

    public function up(Schema $schema): void
    {
        // Create property_definition table
        $this->addSql('CREATE TABLE property_definition (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, required BOOLEAN NOT NULL, multiple BOOLEAN NOT NULL, sort_order INT NOT NULL, validation_rules JSON DEFAULT NULL, default_value JSON DEFAULT NULL, description VARCHAR(1000) DEFAULT NULL, active BOOLEAN NOT NULL, PRIMARY KEY(id))');

        // Create news_item table
        $this->addSql('CREATE TABLE news_item (id SERIAL NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, active_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(50) NOT NULL, PRIMARY KEY(id))');

        // Create property_value table
        $this->addSql('CREATE TABLE property_value (id SERIAL NOT NULL, news_item_id INT NOT NULL, property_definition_id INT NOT NULL, value JSON NOT NULL, sort_order INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8D5E86B3B3A8B49A ON property_value (news_item_id)');
        $this->addSql('CREATE INDEX IDX_8D5E86B3E3A9D49 ON property_value (property_definition_id)');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE property_value ADD CONSTRAINT FK_8D5E86B3B3A8B49A FOREIGN KEY (news_item_id) REFERENCES news_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE property_value ADD CONSTRAINT FK_8D5E86B3E3A9D49 FOREIGN KEY (property_definition_id) REFERENCES property_definition (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Insert default property definitions
        $this->addSql('INSERT INTO property_definition (name, type, required, multiple, sort_order, active, description) VALUES
            (\'title\', \'text\', true, false, 1, true, \'Название элемента\'),
            (\'content\', \'textarea\', false, false, 2, true, \'Основной контент\'),
            (\'image\', \'file\', false, false, 3, true, \'Изображение\'),
            (\'tags\', \'select\', false, true, 4, true, \'Теги или категории\')');
    }

    public function down(Schema $schema): void
    {
        // Drop tables in reverse order
        $this->addSql('ALTER TABLE property_value DROP CONSTRAINT FK_8D5E86B3E3A9D49');
        $this->addSql('ALTER TABLE property_value DROP CONSTRAINT FK_8D5E86B3B3A8B49A');
        $this->addSql('DROP TABLE property_value');
        $this->addSql('DROP TABLE news_item');
        $this->addSql('DROP TABLE property_definition');
    }
}
