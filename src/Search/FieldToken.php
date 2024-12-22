<?php

namespace App\Search;

use ACSEO\TypesenseBundle\Finder\TypesenseQuery;

class FieldToken implements TokenInterface {

    public function getRegex(): string
    {
        return '/[a-zA-Z_][a-zA-Z0-9_-]*:"[^"]+"/';
    }

    public function convertToQuery(array $tokens):string
    {

        $filters = [];
        foreach ($tokens as $token) {
            [$field, $value] = explode(':', $token);
            $filters[$field][] = '`'.trim($value,'"').'`';
        }

        $filtered = [];
        foreach ($filters as $field => $values) {
            $trimmedfield = trim($field,'-');
            $escapedValues = implode(',',$values);
            $escapedValues = '['.$escapedValues.']';

            if(str_ends_with($field, '-')){
                $filtered[] = $trimmedfield . ':!= ' . $escapedValues . ' ';
            } else {
                $orConditions=[];
                foreach ($values as $value) {
                    $orConditions[] = $trimmedfield . ':= ' . $value . ' ';
                }

                $filtered[] = '('.implode(' || ', $orConditions).')';
            }
        }

        return implode(' && ', $filtered);
    }
}