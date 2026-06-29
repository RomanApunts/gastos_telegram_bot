<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Gastos recurrentes (portable).
 */
final class Version20260627152107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabla recurring_expense';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('recurring_expense');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('category_id', Types::INTEGER);
        $table->addColumn('user_id', Types::INTEGER);
        $table->addColumn('amount', Types::DECIMAL, ['precision' => 10, 'scale' => 2]);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('day_of_month', Types::SMALLINT);
        $table->addColumn('active', Types::BOOLEAN);
        $table->addColumn('last_run_period', Types::STRING, ['length' => 7, 'notnull' => false]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['category_id'], 'IDX_F5CC182F12469DE2');
        $table->addIndex(['user_id'], 'IDX_F5CC182FA76ED395');
        $table->addForeignKeyConstraint('category', ['category_id'], ['id'], [], 'FK_F5CC182F12469DE2');
        $table->addForeignKeyConstraint('telegram_user', ['user_id'], ['id'], [], 'FK_F5CC182FA76ED395');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('recurring_expense');
    }
}
