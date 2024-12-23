<?php

namespace App\Search;

use App\Entity\User;

class BooleanToken implements TokenInterface
{
    private ?User $user = null;

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
    public function convertToQuery(array $tokens): string
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

        return implode(' && ', $criteria);
    }
}
