<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250922112552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file (id SERIAL NOT NULL, path VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE news (id SERIAL NOT NULL, news_picture_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, text TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, active_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1DD3995023A6AC6 ON news (news_picture_id)');
        $this->addSql('ALTER TABLE news ADD CONSTRAINT FK_1DD3995023A6AC623A6AC6 FOREIGN KEY (news_picture_id) REFERENCES file (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT fk_8d93d649d60322ac');
        $this->addSql('DROP INDEX idx_8d93d649d60322ac');
        $this->addSql('ALTER TABLE "user" DROP role_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE news DROP CONSTRAINT FK_1DD3995023A6AC623A6AC6');
        $this->addSql('DROP TABLE file');
        $this->addSql('DROP TABLE news');
        $this->addSql('ALTER TABLE "user" ADD role_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT fk_8d93d649d60322ac FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_8d93d649d60322ac ON "user" (role_id)');
    }
}
