<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250923194913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE news (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, text TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN news.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE news_property_value (news_id INT NOT NULL, property_value_id INT NOT NULL, PRIMARY KEY(news_id, property_value_id))');
        $this->addSql('CREATE INDEX IDX_448E9EF0B5A459A0 ON news_property_value (news_id)');
        $this->addSql('CREATE INDEX IDX_448E9EF0A7A78FE6 ON news_property_value (property_value_id)');
        $this->addSql('CREATE TABLE property_definition (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, multiple BOOLEAN NOT NULL, required BOOLEAN NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE property_value (id SERIAL NOT NULL, property_definition_id INT NOT NULL, value TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DB649939C36645B1 ON property_value (property_definition_id)');
        $this->addSql('CREATE TABLE role (id SERIAL NOT NULL, code VARCHAR(100) NOT NULL, name_ru VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_57698A6A77153098 ON role (code)');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, last_name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE news_property_value ADD CONSTRAINT FK_448E9EF0B5A459A0 FOREIGN KEY (news_id) REFERENCES news (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE news_property_value ADD CONSTRAINT FK_448E9EF0A7A78FE6 FOREIGN KEY (property_value_id) REFERENCES property_value (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE property_value ADD CONSTRAINT FK_DB649939C36645B1 FOREIGN KEY (property_definition_id) REFERENCES property_definition (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE news_property_value DROP CONSTRAINT FK_448E9EF0B5A459A0');
        $this->addSql('ALTER TABLE news_property_value DROP CONSTRAINT FK_448E9EF0A7A78FE6');
        $this->addSql('ALTER TABLE property_value DROP CONSTRAINT FK_DB649939C36645B1');
        $this->addSql('DROP TABLE news');
        $this->addSql('DROP TABLE news_property_value');
        $this->addSql('DROP TABLE property_definition');
        $this->addSql('DROP TABLE property_value');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE "user"');
    }
}
