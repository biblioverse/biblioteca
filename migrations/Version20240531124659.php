<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240531124659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename kobo to koboDevice (if any)';
    }

    public function up(Schema $schema): void
    {
        if(false === $this->tableExists($schema, 'kobo')){
            return;
        }
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book_interaction CHANGE read_pages read_pages INT DEFAULT NULL');
        $this->addSql('ALTER TABLE shelf_kobo DROP FOREIGN KEY FK_8CD6C0B1F4C8A5B4');
        $this->addSql('DROP INDEX IDX_8CD6C0B1F4C8A5B4 ON shelf_kobo');
        $this->addSql('DROP INDEX `primary` ON shelf_kobo');
        $this->addSql('ALTER TABLE shelf_kobo CHANGE kobo_id kobo_device_id INT NOT NULL');
        $this->addSql('ALTER TABLE shelf_kobo ADD CONSTRAINT FK_8CD6C0B162A5CBE FOREIGN KEY (kobo_device_id) REFERENCES kobo (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_8CD6C0B162A5CBE ON shelf_kobo (kobo_device_id)');
        $this->addSql('ALTER TABLE shelf_kobo ADD PRIMARY KEY (kobo_device_id, shelf_id)');
        $this->addSql('ALTER TABLE kobo_synced_book DROP FOREIGN KEY FK_2E188774F4C8A5B4');
        $this->addSql('DROP INDEX IDX_2E188774F4C8A5B4 ON kobo_synced_book');
        $this->addSql('ALTER TABLE kobo_synced_book CHANGE kobo_id kobo_device_id INT NOT NULL');
        $this->addSql('ALTER TABLE kobo_synced_book ADD CONSTRAINT FK_2E18877462A5CBE FOREIGN KEY (kobo_device_id) REFERENCES kobo (id)');
        $this->addSql('CREATE INDEX IDX_2E18877462A5CBE ON kobo_synced_book (kobo_device_id)');
        $this->addSql('RENAME table kobo to kobo_device');

    }

    public function down(Schema $schema): void
    {
        if(false === $this->tableExists($schema, 'kobo_device')){
            return;
        }
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shelf_kobo DROP FOREIGN KEY FK_8CD6C0B162A5CBE');
        $this->addSql('DROP INDEX IDX_8CD6C0B162A5CBE ON shelf_kobo');
        $this->addSql('DROP INDEX `PRIMARY` ON shelf_kobo');
        $this->addSql('ALTER TABLE shelf_kobo CHANGE kobo_device_id kobo_id INT NOT NULL');
        $this->addSql('ALTER TABLE shelf_kobo ADD CONSTRAINT FK_8CD6C0B1F4C8A5B4 FOREIGN KEY (kobo_id) REFERENCES kobo (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_8CD6C0B1F4C8A5B4 ON shelf_kobo (kobo_id)');
        $this->addSql('ALTER TABLE shelf_kobo ADD PRIMARY KEY (kobo_id, shelf_id)');
        $this->addSql('ALTER TABLE kobo_synced_book DROP FOREIGN KEY FK_2E18877462A5CBE');
        $this->addSql('DROP INDEX IDX_2E18877462A5CBE ON kobo_synced_book');
        $this->addSql('ALTER TABLE kobo_synced_book CHANGE kobo_device_id kobo_id INT NOT NULL');
        $this->addSql('ALTER TABLE kobo_synced_book ADD CONSTRAINT FK_2E188774F4C8A5B4 FOREIGN KEY (kobo_id) REFERENCES kobo (id)');
        $this->addSql('CREATE INDEX IDX_2E188774F4C8A5B4 ON kobo_synced_book (kobo_id)');
        $this->addSql('ALTER TABLE book_interaction CHANGE read_pages read_pages INT NOT NULL');
        $this->addSql('RENAME table kobo_device to kobo');
    }
}
