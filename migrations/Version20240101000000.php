<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'First migration';
    }

    public function up(Schema $schema): void
    {
        // We skip this migration if the table "book" is already created
        if($this->tableExists($schema, 'book')){
            return;
        }

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE book (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, checksum VARCHAR(255) NOT NULL, summary LONGTEXT DEFAULT NULL, slug VARCHAR(128) NOT NULL, created DATE NOT NULL, updated DATE DEFAULT NULL, image_path VARCHAR(255) DEFAULT NULL, image_filename VARCHAR(255) DEFAULT NULL, book_path VARCHAR(255) NOT NULL, book_filename VARCHAR(255) NOT NULL, serie VARCHAR(255) DEFAULT NULL, serie_index DOUBLE PRECISION DEFAULT NULL, language VARCHAR(2) DEFAULT NULL, publisher VARCHAR(255) DEFAULT NULL, publish_date DATE DEFAULT NULL, authors LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', extension VARCHAR(5) NOT NULL, image_extension VARCHAR(5) DEFAULT NULL, tags LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_CBE5A331DE6FDF9A (checksum), UNIQUE INDEX UNIQ_CBE5A331989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE book_interaction (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, book_id INT NOT NULL, finished TINYINT(1) NOT NULL, favorite TINYINT(1) NOT NULL, INDEX IDX_657AB2DBA76ED395 (user_id), INDEX IDX_657AB2DB16A2B381 (book_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shelf (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(128) NOT NULL, query_string LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_A5475BE3989D9B62 (slug), INDEX IDX_A5475BE3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shelf_book (shelf_id INT NOT NULL, book_id INT NOT NULL, INDEX IDX_431D356F7C12FBC0 (shelf_id), INDEX IDX_431D356F16A2B381 (book_id), PRIMARY KEY(shelf_id, book_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE book_interaction ADD CONSTRAINT FK_657AB2DBA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE book_interaction ADD CONSTRAINT FK_657AB2DB16A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE shelf ADD CONSTRAINT FK_A5475BE3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE shelf_book ADD CONSTRAINT FK_431D356F7C12FBC0 FOREIGN KEY (shelf_id) REFERENCES shelf (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shelf_book ADD CONSTRAINT FK_431D356F16A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {

    }
}
