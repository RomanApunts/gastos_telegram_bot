<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ingresos recurrentes / fijos (portable).
 */
final class Version20260702120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabla recurring_income';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('recurring_income');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('user_id', Types::INTEGER);
        $table->addColumn('amount', Types::DECIMAL, ['precision' => 10, 'scale' => 2]);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('day_of_month', Types::SMALLINT);
        $table->addColumn('active', Types::BOOLEAN);
        $table->addColumn('last_run_period', Types::STRING, ['length' => 7, 'notnull' => false]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_recurring_income_user_id');
        $table->addForeignKeyConstraint('telegram_user', ['user_id'], ['id'], [], 'FK_recurring_income_user');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('recurring_income');
    }
}
