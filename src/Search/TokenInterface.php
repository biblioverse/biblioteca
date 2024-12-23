<?php

namespace App\Search;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.search_token')]
interface TokenInterface
{
    public function getRegex(): string;

    public function setUser(User $user): void;

    public function convertToQuery(array $tokens): string;
}
