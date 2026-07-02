<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ingresos pendientes de confirmación (nota de voz) — portable.
 */
final class Version20260702130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabla pending_income';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('pending_income');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('user_id', Types::INTEGER);
        $table->addColumn('chat_id', Types::BIGINT);
        $table->addColumn('message_id', Types::INTEGER, ['notnull' => false]);
        $table->addColumn('amount', Types::DECIMAL, ['precision' => 10, 'scale' => 2]);
        $table->addColumn('received_at', Types::DATE_IMMUTABLE);
        $table->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_pending_income_user_id');
        $table->addForeignKeyConstraint('telegram_user', ['user_id'], ['id'], [], 'FK_pending_income_user');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('pending_income');
    }
}
