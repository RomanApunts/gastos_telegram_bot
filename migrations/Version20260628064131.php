<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260628064131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pending_expense (id INT AUTO_INCREMENT NOT NULL, chat_id BIGINT NOT NULL, message_id INT DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, spent_at DATE NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, category_id INT DEFAULT NULL, INDEX IDX_E6557A37A76ED395 (user_id), INDEX IDX_E6557A3712469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE pending_expense ADD CONSTRAINT FK_E6557A37A76ED395 FOREIGN KEY (user_id) REFERENCES telegram_user (id)');
        $this->addSql('ALTER TABLE pending_expense ADD CONSTRAINT FK_E6557A3712469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pending_expense DROP FOREIGN KEY FK_E6557A37A76ED395');
        $this->addSql('ALTER TABLE pending_expense DROP FOREIGN KEY FK_E6557A3712469DE2');
        $this->addSql('DROP TABLE pending_expense');
    }
}
