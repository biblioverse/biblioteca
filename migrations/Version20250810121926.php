<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250810121926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE library_folder (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, folder VARCHAR(255) NOT NULL, slug VARCHAR(128) NOT NULL, icon VARCHAR(255) DEFAULT NULL, folder_naming_format VARCHAR(255) DEFAULT NULL, file_naming_format VARCHAR(255) DEFAULT NULL, default_library TINYINT(1) NOT NULL DEFAULT 0, auto_relocation TINYINT(1) NOT NULL DEFAULT 0, volume_identifier VARCHAR(255) NOT NULL DEFAULT 'T', UNIQUE INDEX UNIQ_47409146989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE library_folder_user (library_folder_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_CD5BD978CD0CECE (library_folder_id), INDEX IDX_CD5BD978A76ED395 (user_id), PRIMARY KEY(library_folder_id, user_id)) DEFAULT CHARACTER SET utf8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE library_folder_user ADD CONSTRAINT FK_CD5BD978CD0CECE FOREIGN KEY (library_folder_id) REFERENCES library_folder (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE library_folder_user ADD CONSTRAINT FK_CD5BD978A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE book ADD library_folder_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE book ADD CONSTRAINT FK_CBE5A331CD0CECE FOREIGN KEY (library_folder_id) REFERENCES library_folder (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CBE5A331CD0CECE ON book (library_folder_id)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO library_folder (name, folder, slug, default_library) VALUES ('Default', '/var/wwwh/html/public/books', 'default', 1)
        SQL);

        // TODO REMOVE DEBUG
        $this->addSql(<<<'SQL'
            INSERT INTO library_folder (name, folder, slug, default_library,icon) VALUES ('Second Lib', '/var/wwwh/html/public/bookLib2', 'booklib2', 0,'link-45deg')
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE library_folder_user DROP FOREIGN KEY FK_CD5BD978CD0CECE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE library_folder_user DROP FOREIGN KEY FK_CD5BD978A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE library_folder
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE library_folder_user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE book DROP FOREIGN KEY FK_CBE5A331CD0CECE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CBE5A331CD0CECE ON book
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE book DROP library_folder_id
        SQL);
    }
}
