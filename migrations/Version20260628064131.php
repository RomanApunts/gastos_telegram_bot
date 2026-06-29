<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Gastos pendientes de confirmación (portable).
 */
final class Version20260628064131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabla pending_expense';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('pending_expense');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('user_id', Types::INTEGER);
        $table->addColumn('category_id', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('chat_id', Types::BIGINT);
        $table->addColumn('message_id', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('amount', Types::DECIMAL, ['precision' => 10, 'scale' => 2]);
        $table->addColumn('spent_at', Types::DATE_IMMUTABLE);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_E6557A37A76ED395');
        $table->addIndex(['category_id'], 'IDX_E6557A3712469DE2');
        $table->addForeignKeyConstraint('telegram_user', ['user_id'], ['id'], [], 'FK_E6557A37A76ED395');
        $table->addForeignKeyConstraint('category', ['category_id'], ['id'], [], 'FK_E6557A3712469DE2');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('pending_expense');
    }
}
