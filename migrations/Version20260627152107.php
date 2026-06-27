<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260627152107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recurring_expense (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, description VARCHAR(255) DEFAULT NULL, day_of_month SMALLINT NOT NULL, active TINYINT NOT NULL, last_run_period VARCHAR(7) DEFAULT NULL, created_at DATETIME NOT NULL, category_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_F5CC182F12469DE2 (category_id), INDEX IDX_F5CC182FA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE recurring_expense ADD CONSTRAINT FK_F5CC182F12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE recurring_expense ADD CONSTRAINT FK_F5CC182FA76ED395 FOREIGN KEY (user_id) REFERENCES telegram_user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recurring_expense DROP FOREIGN KEY FK_F5CC182F12469DE2');
        $this->addSql('ALTER TABLE recurring_expense DROP FOREIGN KEY FK_F5CC182FA76ED395');
        $this->addSql('DROP TABLE recurring_expense');
    }
}
