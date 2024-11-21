<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241121135658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add device_id to kobo device';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE kobo_device ADD device_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX kobo_device_id ON kobo_device (device_id);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX kobo_device_id ON kobo_device;');
        $this->addSql('ALTER TABLE kobo_device DROP device_id;');
    }
}
