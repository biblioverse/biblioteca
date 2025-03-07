---
title: Running commands
---

Multiple helper commands are available to help you manage your library. 
For simplicity, only the command is listed here, you will need to prefix it depending on your installation:


With a standard docker compose environment: `docker-compose exec php bin/console <command>`

With ddev: `ddev exec bin/console <command>`

## `app:create-admin-user`
Create an admin user. This commande takes the username and the password as parameters. You should need this command only
during the first installation, as you can create users from the interface.

## `books:check`
Check the integrity of all books.

## `books:extract-cover`                    
Tries to extract the cover from all books that do not have one and saves it in the `public/covers` folder.

## `books:relocate`                       
Relocate all books to their calculated folder. This is necessary only if you want Biblioteca to manage your library structure.

## `books:scan`                              
Will scan the `public/books` folder and add all books to the database. If a book already exists, it will be updated.

## `books:ai`

Check the documentation for this in the AI chapter

## `cache:clear`
Clears the cache

## `doctrine:migrations:migrate`
Executes all missing database migrations.

## `app:backup-db`
Creates a sql backup of the database in the `backups folder`.

## `biblioverse:typesense:populate`
Will re-create the search engine index and re-import all books from the database to the search engine.
