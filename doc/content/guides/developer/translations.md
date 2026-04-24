---
title: Translations
---

Biblioteca is currently available in French and English.

You can easily add new locales by following these steps:

## Adding a new translation

{{% steps %}}

### Add the language to the forms

Edit `UserType.php` and `ProfileType.php`:

{{< filetree/container >}}
  {{< filetree/folder name="src" >}}
    {{< filetree/folder name="Form" >}}
      {{< filetree/file name="UserType.php" >}}
      {{< filetree/file name="ProfileType.php" >}}
    {{< /filetree/folder >}}
  {{< /filetree/folder >}}
{{< /filetree/container >}}

```php
->add('language', ChoiceType::class, [
    'choices' => [
        'English' => 'en',
        'French' => 'fr',
        'name' => 'iso_code',
    ],
])
```

### Create translation files

Create new files for each category in the translations folder:

{{< filetree/container >}}
  {{< filetree/folder name="translations" >}}
    {{< filetree/file name="AutocompleteBundle.en.yaml" >}}
    {{< filetree/file name="AutocompleteBundle.iso_code.yaml" >}}
    {{< filetree/file name="KnpPaginatorBundle.en.yaml" >}}
    {{< filetree/file name="KnpPaginatorBundle.iso_code.yaml" >}}
    {{< filetree/file name="messages+intl-icu.en.yaml" >}}
    {{< filetree/file name="messages+intl-icu.iso_code.yaml" >}}
  {{< /filetree/folder >}}
{{< /filetree/container >}}

### Translate the files

Open the files and translate them.

{{% /steps %}}

{{< callout >}}
If you see missing translations or untranslated strings in the app, please report them so we can add them to the default translations.
{{< /callout >}}
