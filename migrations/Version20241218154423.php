<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241218154423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate to Enums for readstatus and readinglist';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_interaction ADD read_status VARCHAR(255) DEFAULT \'rs-not-started\' NOT NULL, ADD reading_list VARCHAR(255) DEFAULT \'rl-undefined\' NOT NULL');

        $this->addSql('UPDATE book_interaction SET read_status = \'rs-finished\' WHERE finished =1');
        $this->addSql('UPDATE book_interaction SET read_status = \'rs-started\' WHERE finished =0 and read_pages > 0');
        $this->addSql('UPDATE book_interaction SET read_status = \'rs-not-started\' WHERE finished =0 and read_pages = 0');

        $this->addSql('UPDATE book_interaction SET reading_list = \'rl-ignored\' WHERE hidden =1');
        $this->addSql('UPDATE book_interaction SET reading_list = \'rl-to-read\' WHERE hidden =0 and favorite = 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_interaction DROP read_status, DROP reading_list');
    }
}
