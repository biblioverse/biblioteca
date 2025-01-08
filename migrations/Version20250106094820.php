<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250106094820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Configure model contexts';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_model ADD system_prompt LONGTEXT DEFAULT NULL, ADD use_epub_context TINYINT(1) NOT NULL, ADD use_wikipedia_context TINYINT(1) NOT NULL, ADD use_amazon_context TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_model DROP system_prompt, DROP use_epub_context, DROP use_wikipedia_context, DROP use_amazon_context');
    }
}
