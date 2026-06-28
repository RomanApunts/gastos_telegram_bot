<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260628185454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE income (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, description VARCHAR(255) DEFAULT NULL, received_at DATE NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_3FA862D0A76ED395 (user_id), INDEX idx_income_received_at (received_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE income ADD CONSTRAINT FK_3FA862D0A76ED395 FOREIGN KEY (user_id) REFERENCES telegram_user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE income DROP FOREIGN KEY FK_3FA862D0A76ED395');
        $this->addSql('DROP TABLE income');
    }
}
