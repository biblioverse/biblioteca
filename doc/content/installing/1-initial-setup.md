---
title: Install with Docker Compose
weight: 1
toc: false
---

{{< callout type="warning" >}}
A few users have been using Biblioteca for a while now without any major issue. However, as with
any tool, there might be bugs or issues. If you find any, please report them and always make backups of your data
and files.
{{< /callout >}}

{{% steps %}}

### Create a docker-compose.yml file

```yaml {hl_lines=[6,12,13,14,19,20,21,22,34],filename="docker-compose.yml"}
services:
  biblioteca:
    image: ghcr.io/biblioverse/biblioteca:main
    command: ["/bin/sh", "-c" , "apache2-foreground" ]
    ports:
      - 8080
    depends_on:
      - db
    stdin_open: true
    tty: true
    volumes:
      - <cover_folder>:/var/www/html/public/covers
      - <books_folder>:/var/www/html/public/books
      - <image_cache_folder>:/var/www/html/public/media
      - .env:/var/www/html/.env
  db:
    image: mariadb:12.2
    environment:
      - MYSQL_ROOT_PASSWORD=db
      - MYSQL_DATABASE=db
      - MYSQL_USER=db
      - MYSQL_PASSWORD=db
    volumes:
      - mariadb:/var/lib/mysql

  typesense:
    image: typesense/typesense:29.0
    restart: on-failure
    ports:
      - 8983
      - 8108
    volumes:
      - searchdata:/data
    command: '--data-dir /data --api-key=xyz --enable-cors'

volumes:
    mariadb:
    searchdata:
```

- Don't forget to replace the database password and the typesense.
- Change the folders with the path you want to use to store the books on your machine.

### Create a `.env.local` file

```env
APP_SECRET=your_secret
DATABASE_URL=mysql://db:db@db:3306/db
TYPESENSE_KEY=xyz
```

### Start the project

```bash
docker-compose up -d
```

### Create the database and admin user

```bash
docker compose exec biblioteca bin/console doctrine:migration:migrate --no-interaction
docker compose exec biblioteca bin/console app:create-admin-user [name] [password]
docker compose exec biblioteca bin/console biblioverse:typesense:populate
```

### Access the application

Open `http://localhost:8080` and log in with the name and password you set in the previous step.

{{% /steps %}}
