<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240531125758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Kobo Schema';
    }

    public function up(Schema $schema): void
    {
        if(true === $this->tableExists($schema, 'kobo_device')){
            return;
        }

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE kobo_device (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, access_key VARCHAR(255) DEFAULT NULL, force_sync TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_2EB06A2BA76ED395 (user_id), UNIQUE INDEX kobo_access_key (access_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shelf_kobo (kobo_device_id INT NOT NULL, shelf_id INT NOT NULL, INDEX IDX_8CD6C0B162A5CBE (kobo_device_id), INDEX IDX_8CD6C0B17C12FBC0 (shelf_id), PRIMARY KEY(kobo_device_id, shelf_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE kobo_synced_book (id INT AUTO_INCREMENT NOT NULL, book_id INT NOT NULL, kobo_device_id INT NOT NULL, created DATETIME NOT NULL, updated DATETIME DEFAULT NULL, INDEX IDX_2E18877416A2B381 (book_id), INDEX IDX_2E18877462A5CBE (kobo_device_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE kobo_device ADD CONSTRAINT FK_2EB06A2BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE shelf_kobo ADD CONSTRAINT FK_8CD6C0B162A5CBE FOREIGN KEY (kobo_device_id) REFERENCES kobo_device (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shelf_kobo ADD CONSTRAINT FK_8CD6C0B17C12FBC0 FOREIGN KEY (shelf_id) REFERENCES shelf (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE kobo_synced_book ADD CONSTRAINT FK_2E18877416A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE kobo_synced_book ADD CONSTRAINT FK_2E18877462A5CBE FOREIGN KEY (kobo_device_id) REFERENCES kobo_device (id)');
        $this->addSql('ALTER TABLE shelf ADD created DATETIME NULL, ADD updated DATETIME DEFAULT NULL, ADD uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE shelf SET uuid = UUID() where uuid is null');
        $this->addSql('UPDATE shelf SET created = now() where created is null');
        $this->addSql('UPDATE shelf SET updated = now() where updated is null');
        $this->addSql('UPDATE book SET uuid = UUID() where uuid is null');
        $this->addSql('ALTER TABLE shelf modify created datetime not null;');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A5475BE3D17F50A6 ON shelf (uuid)');
    }

    public function down(Schema $schema): void
    {

        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE kobo_device DROP FOREIGN KEY IF EXISTS FK_2EB06A2BA76ED395 ');
        $this->addSql('ALTER TABLE shelf_kobo DROP FOREIGN KEY IF EXISTS FK_8CD6C0B162A5CBE');
        $this->addSql('ALTER TABLE shelf_kobo DROP FOREIGN KEY IF EXISTS FK_8CD6C0B17C12FBC0');
        $this->addSql('ALTER TABLE kobo_synced_book DROP FOREIGN KEY IF EXISTS FK_2E18877416A2B381');
        $this->addSql('ALTER TABLE kobo_synced_book DROP FOREIGN KEY IF EXISTS FK_2E18877462A5CBE');
        $this->addSql('DROP TABLE kobo_device');
        $this->addSql('DROP TABLE shelf_kobo');
        $this->addSql('DROP TABLE kobo_synced_book');
        $this->addSql('DROP INDEX UNIQ_A5475BE3D17F50A6 ON shelf');
        $this->addSql('ALTER TABLE shelf DROP created, DROP updated, DROP uuid');
    }
}
