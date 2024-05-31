<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240505101909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add openAI key';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD open_aikey VARCHAR(255) DEFAULT NULL, ADD book_summary_prompt LONGTEXT DEFAULT NULL, ADD book_keyword_prompt LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP open_aikey, DROP book_summary_prompt, DROP book_keyword_prompt');
    }
}
