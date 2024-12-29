<?php

namespace App\Search;

use App\Entity\User;

class OrderToken implements TokenInterface
{
    private string $orderString = '';

    #[\Override]
    public function getRegex(): string
    {
        return '/orderBy:[a-zA-Z-"]+/';
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
            [, $value] = explode(':', (string) $token);
            $filters[] = trim(trim($value), '"');
        }

        foreach ($filters as $values) {
            $ord = explode('-', $values);
            $this->orderString = $ord[0].'(missing_values: last):'.$ord[1].' ';
        }
    }

    #[\Override]
    public function getFilterQuery(): string
    {
        return '';
    }

    #[\Override]
    public function getOrderQuery(): string
    {
        return $this->orderString;
    }
}
