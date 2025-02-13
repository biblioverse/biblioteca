<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250213182540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove deprecated AI config';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_model DROP use_epub_context, DROP use_wikipedia_context, DROP use_amazon_context');
        $this->addSql('ALTER TABLE user DROP book_summary_prompt, DROP book_keyword_prompt');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_model ADD use_epub_context TINYINT(1) DEFAULT 0 NOT NULL, ADD use_wikipedia_context TINYINT(1) DEFAULT 0 NOT NULL, ADD use_amazon_context TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE `user` ADD book_summary_prompt LONGTEXT DEFAULT NULL, ADD book_keyword_prompt LONGTEXT DEFAULT NULL');
    }
}
