<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Exception\TableDoesNotExist;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;

abstract class AbstractMigration extends \Doctrine\Migrations\AbstractMigration
{
    protected function tableExists(Schema $schema, string $table): bool
    {
        try{
            $schema->getTable($table);
            return true;
        }catch (TableDoesNotExist|SchemaException){
            return false;
        }
    }
}