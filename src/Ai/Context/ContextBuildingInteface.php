<?php

namespace App\Ai\Context;

use App\Entity\Book;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.ai_context_builder', ['priority' => 20])]
interface ContextBuildingInteface
{
    public function isEnabled(): bool;

    public function getContextForPrompt(Book $book): string;
}
