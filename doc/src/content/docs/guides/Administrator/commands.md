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

Check the documentation for this in the AI chapter.

## `books:ai-organize`

**AI-powered library organization** - Runs all harmonization commands in optimal order: authors → series → tags.

```bash
# Preview changes (no modifications)
books:ai-organize --language fr

# Apply all changes
books:ai-organize --language fr --apply

# Skip specific steps
books:ai-organize --language fr --skip-authors --apply
books:ai-organize --language fr --skip-series --skip-tags --apply
```

Options:
- `--language` / `-l`: Target language for genres (fr, en, de, es). Default: en
- `--apply` / `-a`: Apply changes (preview mode by default)
- `--skip-authors`: Skip author harmonization
- `--skip-series`: Skip series/title harmonization
- `--skip-tags`: Skip tag harmonization

## `books:authors-harmonize`

**Harmonize author names** - Unifies author name variations across your library.

Examples of harmonization:
- "J.R.R. Tolkien", "JRR Tolkien", "Tolkien, J.R.R." → "J.R.R. Tolkien"
- "Asimov, Isaac", "Isaac Asimov" → "Isaac Asimov"

```bash
books:authors-harmonize           # Preview
books:authors-harmonize --apply   # Apply
```

## `books:series-harmonize`

**Detect series and clean titles** - Identifies book series, assigns series index, and cleans up redundant information from titles.

Examples:
- "Dune 2 Dune Messiah" → title: "Dune Messiah", series: "Dune", index: 2
- "Foundation 1" → title: "Foundation", series: "Foundation", index: 1

```bash
books:series-harmonize --language fr           # Preview
books:series-harmonize --language fr --apply   # Apply
```

Series names are kept in their original language (e.g., "Foundation" stays "Foundation", not translated).

## `books:tags-harmonize`

**Assign genres from predefined list** - Analyzes each book and assigns 1-2 genres from a curated list of 37 literary genres.

Available genres (French): Science-Fiction, Fantastique, Policier, Thriller, Romance, Roman historique, Aventure, Horreur, Humour, Classique, Littérature jeunesse, Biographie, Essai, Poésie, Théâtre, Contes et légendes, Dystopie, Space Opera, Philosophie, Drame, Autobiographie, Nouvelle, Roman psychologique, Théologie, Religion, Histoire, Voyage, Politique, Économie, Art, Cuisine, Développement personnel, Guerre, Satire, Bande dessinée, Manga, Érotique, Littérature générale

```bash
books:tags-harmonize --language fr           # Preview
books:tags-harmonize --language fr --apply   # Apply (replaces existing tags)
books:tags-harmonize --language fr --mode add --apply  # Add genres to existing tags
```

Options:
- `--mode`: `replace` (default) replaces all tags, `add` keeps existing tags

## `cache:clear`
Clears the cache

## `doctrine:migrations:migrate`
Executes all missing database migrations.

## `app:backup-db`
Creates a sql backup of the database in the `backups folder`.

## `biblioverse:typesense:populate`
Will re-create the search engine index and re-import all books from the database to the search engine.
