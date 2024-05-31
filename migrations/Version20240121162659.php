<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240121162659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add age_category and birthday';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book ADD age_category INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD birthday DATE DEFAULT NULL, ADD max_age_category INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book DROP age_category');
        $this->addSql('ALTER TABLE `user` DROP birthday, DROP max_age_category');
    }
}
