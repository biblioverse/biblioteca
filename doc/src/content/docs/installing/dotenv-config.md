---
title: Dotenv configuration
---

The following properties can be set in a `.env.local` file in the root of the project:
    
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
KOBO_PROXY_USE_EVERYWHERE=0
KOBO_PROXY_ENABLED=1
ALLOW_BOOK_RELOCATION=1
EPUB_METADATA_EMBED_ENABLED=1
MAILER_DSN=smtp://user:pass@smtp.example.com:587
SMTP_FROM_EMAIL=noreply@example.com
SMTP_FROM_NAME=Biblioteca
SMTP_MAX_FILE_SIZE=25
```

- `APP_ENV`: The environment the application is running in. This can be `dev` or `prod`. You should always use `prod` unless you need to debug the application.
- `APP_SECRET`: A secret key used to secure the application. Make it unique!
- `DATABASE_URL`: The URL to the database. You should not need to change this unless you are using a different database.
- `MESSENGER_TRANSPORT_DSN`: Do not change it.
- `MAILER_DSN`: Mailer DSN for sending ebooks to e-readers via email. See [Sending Books to E-readers](../guides/user/send-to-ereader) for user documentation.
- `TYPESENSE_URL`: The URL to the typesense server. You should not need to change this unless you are using a different server.
- `TYPESENSE_KEY`: The key to access the typesense server. Needs to correspond to the one you set in your docker-compose file.
- `TYPESENSE_EMBED_MODEL`: Embed model, default is the built-in `ts/all-MiniLM-L12-v2` model. You can use an OpenAi/Palm/Vertex model. Example: `openai/text-embedding-3-small`.
- `TYPESENSE_EMBED_NUM_DIM`: Dimension of the embed model. `all-MiniLM-L12-v2` is 384, `text-embedding-3-small` is 1536. Read your model documentation to know the dimension.
- `TYPESENSE_EMBED_KEY`: Authentication for the embed model. Default is `~`, but you can set a token if you use an external model.
- `BOOK_FOLDER_NAMING_FORMAT`: The format to use to name the folders where the books are stored. You can use the following placeholders: `{authorFirst}`, `{author}`, `{title}`, `{serie}`.
- `MAILER_DSN`: Mailer DSN for sending ebooks to e-readers. This uses Symfony's Mailer component. Examples:
  - SMTP: `smtp://user:pass@smtp.example.com:587`
  - SMTP with TLS: `smtp://user:pass@smtp.example.com:587?encryption=tls`
  - SMTP with SSL: `smtp://user:pass@smtp.example.com:465?encryption=ssl`
  - For cloud services, you can use provider-specific DSNs (see [Symfony Mailer documentation](https://symfony.com/doc/current/mailer.html))
- `SMTP_FROM_EMAIL`: Default "from" email address for sent emails.
- `SMTP_FROM_NAME`: Default "from" name for sent emails (default: `Biblioteca`).
- `SMTP_MAX_FILE_SIZE`: Maximum file size in MB for email attachments (default: 25).
- `BOOK_FILE_NAMING_FORMAT`: The format to use to name the files where the books are stored. You can use the following placeholders: `{serie}`, `{serieIndex}`, `{title}`.
- `KOBO_PROXY_USE_DEV`: If set to `1`, the kobo proxy will be used in development.
- `KOBO_PROXY_USE_EVERYWHERE`: If set to `1`, the kobo proxy will be used everywhere and all request will be forwarded to the original store.
- `KOBO_PROXY_ENABLED`: If set to `0`, the kobo proxy will be disabled.
- `ALLOW_BOOK_RELOCATION`: If set to `0`, the books will not be moved to the correct folder when added to the library. This is useful if you want to manage the folder structure yourself.
- `REMOTE_USER_LOGIN_HEADER_NAME`: You can handle authentication from your proxy and read the HTTP Header to authenticate the user. Default value is empty string, Example: `HTTP_X_TOKEN_SUBJECT`. User must exist on the database.
- `EPUB_METADATA_EMBED_ENABLED`: If set to `0`, EPUB downloads will use the original file without embedding library metadata.


