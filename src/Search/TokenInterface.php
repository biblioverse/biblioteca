<?php

namespace App\Search;

use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.search_token')]
interface TokenInterface
{
    public function getRegex():string;

    public function convertToQuery(array $tokens):string;
}