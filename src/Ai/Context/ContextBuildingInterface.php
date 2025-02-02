<?php

namespace App\Ai\Context;

use App\Entity\AiModel;
use App\Entity\Book;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.ai_context_builder', ['priority' => 20])]
interface ContextBuildingInterface
{
    public function isEnabled(AiModel $aiModel, ?Book $book = null): bool;

    public function getContextForPrompt(Book $book): string;
}
