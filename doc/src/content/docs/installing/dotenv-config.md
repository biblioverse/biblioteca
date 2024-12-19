---
title: Dotenv configuration
---

The following properties can be set in a `.env` file in the root of the project:
    
```dotenv
APP_ENV=prod
APP_SECRET=9653a6c476d291323d2db7417c13a814
DATABASE_URL="mysql://db:db@db:3306/db?serverVersion=mariadb-10.3.39&charset=utf8"
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
MAILER_DSN=native://default
TYPESENSE_URL=http://typesense:8108
TYPESENSE_KEY=xyz
BOOK_FOLDER_NAMING_FORMAT="{authorFirst}/{author}/{title}/{serie}"
BOOK_FILE_NAMING_FORMAT="{serie}-{serieIndex}-{title}"
KOBO_PROXY_USE_DEV=0
KOBO_PROXY_USE_EVERYWHERE=0
KOBO_PROXY_ENABLED=1
ALLOW_BOOK_RELOCATION=1
```

- `APP_ENV`: The environment the application is running in. This can be `dev` or `prod`. You should always use `prod` unless you need to debug the application.
- `APP_SECRET`: A secret key used to secure the application. Make it unique!
- `DATABASE_URL`: The URL to the database. You should not need to change this unless you are using a different database.
- `MESSENGER_TRANSPORT_DSN`: Do not change it.
- `MAILER_DSN`: Currently not used.
- `TYPESENSE_URL`: The URL to the typesense server. You should not need to change this unless you are using a different server.
- `TYPESENSE_KEY`: The key to access the typesense server. Needs to correspond to the one you set in your docker-compose file.
- `BOOK_FOLDER_NAMING_FORMAT`: The format to use to name the folders where the books are stored. You can use the following placeholders: `{authorFirst}`, `{author}`, `{title}`, `{serie}`.
- `BOOK_FILE_NAMING_FORMAT`: The format to use to name the files where the books are stored. You can use the following placeholders: `{serie}`, `{serieIndex}`, `{title}`.
- `KOBO_PROXY_USE_DEV`: If set to `1`, the kobo proxy will be used in development.
- `KOBO_PROXY_USE_EVERYWHERE`: If set to `1`, the kobo proxy will be used everywhere and all request will be forwarded to the original store.
- `KOBO_PROXY_ENABLED`: If set to `0`, the kobo proxy will be disabled.
- `ALLOW_BOOK_RELOCATION`: If set to `0`, the books will not be moved to the correct folder when added to the library. This is useful if you want to manage the folder structure yourself.