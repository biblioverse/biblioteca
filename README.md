# Biblioteca

Biblioteca is a web application made to manage large ebook libraries and is developed aiming to help you to have consistent and well
classified libraries. 


## Pre-requirements
- Docker and `docker-compose` installed

## Documentation
- [Setup the docker environment and dependencies](doc/install.md)
- [Upgrade to a newer version](doc/update.md)
- [Console Commands](doc/commands.md)
- [Adding books](doc/adding-books.md)



# Upload some books

* Push your books to the `public/books` folder
* Run `docker compose exec biblioteca php bin/console books:scan`
