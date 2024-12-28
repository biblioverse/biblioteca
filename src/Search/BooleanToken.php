<?php

namespace App\Search;

use App\Entity\User;

class BooleanToken implements TokenInterface
{
    private ?User $user = null;

    private $filterString='';
    private $orderString='';

    #[\Override]
    public function getRegex(): string
    {
        return '/#\w+/';
    }

    #[\Override]
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    #[\Override]
    public function parseTokens(array $tokens): void
    {
        $criteria = [];
        foreach ($tokens as $token) {
            $token = trim((string) $token, '#');

            $field = str_starts_with($token, 'not_') ? substr($token, 4) : $token;

            if (in_array($field, ['hidden', 'read', 'favorite'], true)) {
                if (!$this->user instanceof User) {
                    continue;
                }
                if (str_starts_with($token, 'not_')) {
                    $criteria[] = $field.':!=['.$this->user->getId().']';
                } else {
                    $criteria[] = $field.':=['.$this->user->getId().']';
                }
            } elseif (str_starts_with($token, 'not_')) {
                $criteria[] = $field.':=false';
            } else {
                $criteria[] = $field.':=true';
            }
        }

        $this->filterString = implode(' && ', $criteria);
    }

    public function getFilterQuery(): string
    {
        return $this->filterString;
    }

    public function getOrderQuery(): string
    {
        return $this->orderString;
    }


}
