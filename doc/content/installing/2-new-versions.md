---
title: Updating to new versions
weight: 3
---

{{< callout type="info" >}}
As a developer, you can use the script `./update.sh` to update the application. If you have local changes in the application code, they will be overwritten by the latest release. 
{{< /callout >}}

{{% steps %}}

### Update the docker image

```bash
docker-compose pull
docker-compose up -d
```

### Update the database schema

```bash
docker compose exec biblioteca bin/console doctrine:migration:migrate --no-interaction
```

### Update the typesense schema (if needed)

```bash
docker-compose exec biblioteca bin/console biblioverse:typesense:populate
```

### Clear the cache

```bash
docker-compose exec biblioteca bin/console cache:clear
```

{{% /steps %}}
