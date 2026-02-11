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

For finer control (e.g., excluding specific books or authors), run the individual commands below.

## `books:authors-harmonize`

**Harmonize author names** - Unifies author name variations across your library.

Examples of harmonization:
- "J.R.R. Tolkien", "JRR Tolkien", "Tolkien, J.R.R." → "J.R.R. Tolkien"
- "Asimov, Isaac", "Isaac Asimov" → "Isaac Asimov"

```bash
books:authors-harmonize           # Preview
books:authors-harmonize --apply   # Apply
books:authors-harmonize --exclude="J.R.R. Tolkien,Stephen King"  # Skip specific authors
```

Options:
- `--apply` / `-a`: Apply changes (preview mode by default)
- `--exclude`: Comma-separated list of author names to skip

## `books:series-harmonize`

**Detect series and clean titles** - Identifies book series, assigns series index, and cleans up redundant information from titles.

Examples:
- "Dune 2 Dune Messiah" → title: "Dune Messiah", series: "Dune", index: 2
- "Foundation 1" → title: "Foundation", series: "Foundation", index: 1

```bash
books:series-harmonize           # Preview
books:series-harmonize --apply   # Apply
books:series-harmonize --exclude=84,142,143  # Skip specific book IDs
```

Options:
- `--apply` / `-a`: Apply changes (preview mode by default)
- `--exclude`: Comma-separated list of book IDs to skip

Series names are kept in their original language (e.g., "Foundation" stays "Foundation", not translated).

## `books:tags-harmonize`

**Harmonize tags and assign genres** - Assigns 1-2 main genres from a predefined list, curates existing tags (keeps meaningful ones, removes junk), and may add descriptive tags.

The hybrid approach:
1. Assigns 1-2 main genres from a curated list of 37 literary genres
2. Preserves meaningful existing tags (themes, settings, mood, etc.)
3. Removes junk tags ("Book", "Ebook", "General", "Fiction", etc.)
4. May add 1-2 new descriptive tags when clearly applicable

```bash
books:tags-harmonize --language fr           # Preview
books:tags-harmonize --language fr --apply   # Apply (replaces existing tags)
books:tags-harmonize --language fr --mode add --apply  # Add genres to existing tags
books:tags-harmonize --language fr --exclude=42,99     # Skip specific book IDs
```

Options:
- `--apply` / `-a`: Apply changes (preview mode by default)
- `--language` / `-l`: Target language for genres (fr, en, de, es). Default: en
- `--mode` / `-m`: `replace` (default) replaces all tags, `add` keeps existing tags
- `--exclude`: Comma-separated list of book IDs to skip

## `cache:clear`
Clears the cache

## `doctrine:migrations:migrate`
Executes all missing database migrations.

## `app:backup-db`
Creates a sql backup of the database in the `backups folder`.

## `biblioverse:typesense:populate`
Will re-create the search engine index and re-import all books from the database to the search engine.
