---
title: Themes
---

Biblioteca is configured to be easily themeable.

By default all the template in the `templates` directory are used unless the same template exists in the theme's directory.

## Creating a new theme

{{% steps %}}

### Add your theme in the user form

Edit `src/Form/ProfileType.php`:

{{< filetree/container >}}
  {{< filetree/folder name="src" >}}
    {{< filetree/folder name="Form" >}}
      {{< filetree/file name="ProfileType.php" >}}
    {{< /filetree/folder >}}
  {{< /filetree/folder >}}
{{< /filetree/container >}}

```php
->add('theme', ChoiceType::class, [
    'label' => 'Theme',
    'choices' => [
        'Default' => 'default',
        'Dark' => 'dark',
        'Cool Theme' => 'cool',
    ],
])
```

### Override templates

You can overwrite all the original templates by adding a file with the same structure under your theme directory:

In `templates/themes/<your theme>/[original_folder]/[original_template_name]` you can rewrite the template with your theme markup

Every time a template is called, it will first check if the template exists in the theme's folder or fall back to the original one

{{< filetree/container >}}
  {{< filetree/folder name="templates" >}}
    {{< filetree/folder name="shelf" >}}
      {{< filetree/file name="index.html.twig" >}}
    {{< /filetree/folder >}}
    {{< filetree/folder name="themes" >}}
      {{< filetree/folder name="cool" >}}
        {{< filetree/folder name="shelf" >}}
          {{< filetree/file name="index.html.twig" >}}
        {{< /filetree/folder >}}
      {{< /filetree/folder >}}
    {{< /filetree/folder >}}
  {{< /filetree/folder >}}
{{< /filetree/container >}}

### Update CSS assets

{{< filetree/container >}}
  {{< filetree/folder name="assets" >}}
    {{< filetree/folder name="styles" >}}
      {{< filetree/file name="global.css" >}}
    {{< /filetree/folder >}}
  {{< /filetree/folder >}}
{{< /filetree/container >}}

### Test the theme

Don't forget to change your theme in the user settings to test it.

{{% /steps %}}
