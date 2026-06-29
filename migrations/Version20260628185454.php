<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ingresos (portable).
 */
final class Version20260628185454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabla income';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('income');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('user_id', Types::INTEGER);
        $table->addColumn('amount', Types::DECIMAL, ['precision' => 10, 'scale' => 2]);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('received_at', Types::DATE_IMMUTABLE);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_3FA862D0A76ED395');
        $table->addIndex(['received_at'], 'idx_income_received_at');
        $table->addForeignKeyConstraint('telegram_user', ['user_id'], ['id'], [], 'FK_3FA862D0A76ED395');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('income');
    }
}
