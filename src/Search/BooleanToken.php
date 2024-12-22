<?php

namespace App\Search;

use ACSEO\TypesenseBundle\Finder\TypesenseQuery;

class BooleanToken implements TokenInterface {

    public function getRegex(): string
    {
        return '/#\w+/';
    }

    public function convertToQuery(array $tokens): string
    {
        $criteria =[];
        foreach ($tokens as $token) {
            $token = trim($token,'#');
            if(str_starts_with($token, 'not_')){
                $criteria[] = str_replace('not_','',$token).':=false';
            } else {
                $criteria[] = $token.':=true';
            }
        }
        return implode(' && ', $criteria);
    }
}