<?php

namespace App\Ai\Prompt;

use App\Entity\Book;

class SearchHintPrompt implements BookPromptInterface
{
    private string $prompt;

    #[\Override]
    public function getPrompt(): string
    {
        return $this->prompt;
    }

    #[\Override]
    public function setPrompt(string $prompt): void
    {
        $this->prompt = $prompt;
    }

    #[\Override]
    public function initialisePrompt(): void
    {
    }

    #[\Override]
    public function getBook(): Book
    {
        return new Book();
    }

    #[\Override]
    public function convertResult(string $result): array
    {
        $result = trim($result, '´`');
        if (str_starts_with($result, 'json')) {
            $result = substr($result, 4);
        }

        try {
            $decode = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decode)) {
                return $decode;
            }
        } catch (\JsonException) {
        }

        return ['filter_by' => $result];
    }

    public function getTypesenseNaturalLanguagePrompt(array $series, array $authors, array $tags): string
    {
        $baseprompt = '
You are assisting a user in searching for books. Convert their query into the appropriate Typesense query format based on the instructions below.

### Typesense Query Syntax ###

## Filtering ##

Matching values: The syntax is {fieldName} follow by a match operator := and a string value or an array of string values each separated by a comma. 
Do not encapsulate the value in double quote or single quote surround the value with backticks to escape them.
Examples:
- authors:=Homer
- authors:=[Homer, Jules Verne] returns books that are authored by Homer or Jules Verne.
- tags:=[thriller,action] returns books that are tagged as thriller or action books.

Numeric Filters: Use :[min..max] for ranges, or comparison operators like :>, :<, :>=, :<=, :=. Examples:
 - serieIndex:[3..5]
 
Multiple Conditions on different fields: Separate conditions with &&. Examples:
 - authors:Blake Pierce && serie:=[Jessie Hunt, Fiona Reed]
 - tags:=Shoes && serie:=Outdoor

OR Conditions Across Fields: Use || but only for different fields. if the conditions are for the same field, use the array notation. 
Surround the condition with parentheses. 
Examples:
 - age:=1 || tags:=rock
 - (age:=1 || authors:=Homer) && tags:=Action

Negation: Use :!= to exclude values. 
Examples:
 - tags:!=Action
 - authors:!=[Homer, Jules Verne]

Multiple conditions on the same field: use the multi-value OR syntax. For eg:
\`tags:[Action, Comedy, Adult]\`

If any string values have parentheses, surround the value with backticks to escape them.

For eg, if a field has the value "Batman (Detective Comics)", and you need to use it in a filter_by expression, then you would use it like this:
- serie:=\`Batman (Detective Comics)\`
- serie!:=\`Batman (Detective Comics)\`

use the extension to filter for epub, cbr, cbz, pdf, mobi files.
- Comics: extension:=[cbr,cbz,pdf]
- Manga: extension:=[cbr,cbz,pdf]
- Novels: extension:=[epub,pdf]


### Query ###
Include query only if filter_by is inadequate.

## Book properties ##

| Name | Data Type | Enum Values | Description |
|------|-----------|-------------|-------------|
|title|string|||
|serie|string| {series}|There are more enum values for this field|
|summary|string| ||
|serieIndex|int32|||
|extension|string| epub,cbr,cbz,pdf,mobi||
|authors|string[]| {authors}|There are more enum values for this field|
|verified|bool|true, false||
|tags|string[]| {tags}|There are more enum values for this field|
|updated|datetime|||
|age|int32|1||
|read|int32|1||
|hidden|int32|1||
|favorite|int32|1||
          

### Output Instructions ###
Provide the valid JSON with the correct filter format, only include fields with non-null values. Do not add extra text or explanations.`,
';

        return str_replace(['{series}', '{authors}', '{tags}'], [implode(',', $series), implode(',', $authors), implode(',', $tags)], $baseprompt);
    }

    #[\Override]
    public function replaceBookOccurrence(string $prompt): string
    {
        return $prompt;
    }
}
