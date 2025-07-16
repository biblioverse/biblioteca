---
title: Filesystem and relocation
---

You can manage the file structure of your book by hand and run a command to have them in biblioteca, or you can ask biblioteca
to relocate them to a path format of your choice.

## Available placeholders
```
{author} => Author, lowercase, slugified,
{author-uc} => Author, first letters capitalized,
{author-raw} => Author raw,
{authorFirst} => First letter of author's name,
{title} => Title, lowercase, slugified,
{title-uc} => Title, first letters capitalized
{title-raw} => Title raw
{serie} => serie, lowercase, slugified
{serie-uc} => Serie, first letters capitalized
{serie-raw} => Serie raw
{serieIndex} => Index in serie
{language} => Language of the book,
{extension} => Book extension,
```