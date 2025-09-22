<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250922155119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE news_item ADD name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE news_item DROP updated_at');
        $this->addSql('ALTER TABLE news_item DROP active_at');
        $this->addSql('ALTER TABLE news_item DROP status');
        $this->addSql('ALTER TABLE news_item ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN news_item.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE property_definition DROP validation_rules');
        $this->addSql('ALTER TABLE property_value DROP CONSTRAINT fk_8d5e86b3b3a8b49a');
        $this->addSql('ALTER TABLE property_value DROP CONSTRAINT fk_8d5e86b3e3a9d49');
        $this->addSql('ALTER TABLE property_value ADD CONSTRAINT FK_DB649939458B4EB8 FOREIGN KEY (news_item_id) REFERENCES news_item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE property_value ADD CONSTRAINT FK_DB649939C36645B1 FOREIGN KEY (property_definition_id) REFERENCES property_definition (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_8d5e86b3b3a8b49a RENAME TO IDX_DB649939458B4EB8');
        $this->addSql('ALTER INDEX idx_8d5e86b3e3a9d49 RENAME TO IDX_DB649939C36645B1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE news_item ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE news_item ADD active_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE news_item ADD status VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE news_item DROP name');
        $this->addSql('ALTER TABLE news_item ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN news_item.created_at IS NULL');
        $this->addSql('ALTER TABLE property_value DROP CONSTRAINT FK_DB649939458B4EB8');
        $this->addSql('ALTER TABLE property_value DROP CONSTRAINT FK_DB649939C36645B1');
        $this->addSql('ALTER TABLE property_value ADD CONSTRAINT fk_8d5e86b3b3a8b49a FOREIGN KEY (news_item_id) REFERENCES news_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE property_value ADD CONSTRAINT fk_8d5e86b3e3a9d49 FOREIGN KEY (property_definition_id) REFERENCES property_definition (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER INDEX idx_db649939458b4eb8 RENAME TO idx_8d5e86b3b3a8b49a');
        $this->addSql('ALTER INDEX idx_db649939c36645b1 RENAME TO idx_8d5e86b3e3a9d49');
        $this->addSql('ALTER TABLE property_definition ADD validation_rules JSON DEFAULT NULL');
    }
}
