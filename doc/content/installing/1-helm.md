---
title: Install with Helm
weight: 2
toc: false
---

{{< callout type="warning" >}}
A few users have been using Biblioteca for a while now without any major issue. However, as with
any tool, there might be bugs or issues. If you find any, please report them and always make backups of your data
and files.
{{< /callout >}}

{{% steps %}}

### Create a values.yml file

```yaml {hl_lines=[3],filename="values.yml"}
biblioteca:
  appSecret:
    appSecret: zafmqUbgaMQbx4wCFbZSpwsQ34Dw7wUd

persistence:
  enabled: true
```

Check the [Helm Chart](https://github.com/biblioverse/helm/blob/main/charts/biblioteca/README.md) for more information on the configuration options.

We recommend you set at least the `appSecret` values and decide if you want to persist any data.

### Install the Helm Chart

```bash
helm repo add biblioverse https://biblioverse.github.io/helm/
helm install biblioteca biblioverse/biblioteca -f values.yml
```

### Finalize installation

Once installed, the Helm chart will output the instructions to finalize the app's installation and the creation of your first admin user.

### Access the application

Access the application at the URL given to you by the `helm install` command.

Log in with the name and password you set in the previous step.

{{% /steps %}}
