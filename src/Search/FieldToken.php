<?php

namespace App\Search;

use App\Entity\User;

class FieldToken implements TokenInterface
{
    private $filterString='';

    #[\Override]
    public function getRegex(): string
    {
        return '/[a-zA-Z_][a-zA-Z0-9_-]*:"[^"]+"/';
    }

    #[\Override]
    public function setUser(User $user): void
    {
    }

    #[\Override]
    public function parseTokens(array $tokens): void
    {
        $filters = [];
        foreach ($tokens as $token) {
            [$field, $value] = explode(':', (string) $token);
            $filters[$field][] = '`'.trim($value, '"').'`';
        }

        $filtered = [];
        foreach ($filters as $field => $values) {
            if($field==='orderBy'){
                continue;
            }
            $trimmedfield = trim($field, '-');

            $escapedValues = implode(',', $values);
            $escapedValues = '['.$escapedValues.']';

            if (str_ends_with($field, '-')) {
                $filtered[] = $trimmedfield.':!= '.$escapedValues.' ';
            } else {
                $orConditions = [];
                foreach ($values as $value) {
                    $orConditions[] = $trimmedfield.':= '.$value.' ';
                }

                $filtered[] = '('.implode(' || ', $orConditions).')';
            }
        }

        $this->filterString = implode(' && ', $filtered);
    }

    public function getFilterQuery(): string
    {
        return $this->filterString;
    }

    public function getOrderQuery(): string
    {
        return '';
    }


}
