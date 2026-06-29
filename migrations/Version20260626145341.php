<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Esquema inicial (portable): telegram_user, category, expense, category_budget.
 * Escrita con la Schema API para que el DDL se genere por motor (MySQL/MariaDB,
 * PostgreSQL, SQLite…).
 */
final class Version20260626145341 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Esquema inicial: telegram_user, category, expense, category_budget';
    }

    public function up(Schema $schema): void
    {
        $telegramUser = $schema->createTable('telegram_user');
        $telegramUser->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $telegramUser->addColumn('telegram_id', Types::BIGINT);
        $telegramUser->addColumn('name', Types::STRING, ['length' => 100]);
        $telegramUser->addColumn('active', Types::BOOLEAN);
        $telegramUser->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $telegramUser->setPrimaryKey(['id']);
        $telegramUser->addUniqueIndex(['telegram_id'], 'UNIQ_F180F059CC0B3066');

        $category = $schema->createTable('category');
        $category->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $category->addColumn('name', Types::STRING, ['length' => 60]);
        $category->addColumn('active', Types::BOOLEAN);
        $category->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $category->setPrimaryKey(['id']);
        $category->addUniqueIndex(['name'], 'UNIQ_64C19C15E237E06');

        $categoryBudget = $schema->createTable('category_budget');
        $categoryBudget->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $categoryBudget->addColumn('category_id', Types::INTEGER);
        $categoryBudget->addColumn('amount', Types::DECIMAL, ['precision' => 10, 'scale' => 2]);
        $categoryBudget->addColumn('effective_from', Types::DATE_IMMUTABLE);
        $categoryBudget->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $categoryBudget->setPrimaryKey(['id']);
        $categoryBudget->addIndex(['category_id'], 'IDX_7C1A3D0012469DE2');
        $categoryBudget->addIndex(['category_id', 'effective_from'], 'idx_budget_cat_from');
        $categoryBudget->addForeignKeyConstraint('category', ['category_id'], ['id'], [], 'FK_7C1A3D0012469DE2');

        $expense = $schema->createTable('expense');
        $expense->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $expense->addColumn('category_id', Types::INTEGER);
        $expense->addColumn('user_id', Types::INTEGER);
        $expense->addColumn('amount', Types::DECIMAL, ['precision' => 10, 'scale' => 2]);
        $expense->addColumn('description', Types::STRING, ['length' => 255, 'notnull' => false]);
        $expense->addColumn('spent_at', Types::DATE_IMMUTABLE);
        $expense->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $expense->setPrimaryKey(['id']);
        $expense->addIndex(['category_id'], 'IDX_2D3A8DA612469DE2');
        $expense->addIndex(['user_id'], 'IDX_2D3A8DA6A76ED395');
        $expense->addIndex(['spent_at'], 'idx_expense_spent_at');
        $expense->addForeignKeyConstraint('category', ['category_id'], ['id'], [], 'FK_2D3A8DA612469DE2');
        $expense->addForeignKeyConstraint('telegram_user', ['user_id'], ['id'], [], 'FK_2D3A8DA6A76ED395');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('expense');
        $schema->dropTable('category_budget');
        $schema->dropTable('category');
        $schema->dropTable('telegram_user');
    }
}
