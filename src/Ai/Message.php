<?php

namespace App\Ai;

use App\Enum\AiMessageRole;

class Message
{
    public ?array $suggestions = null;
    public \DateTimeImmutable $date;

    public function __construct(public string $text, public AiMessageRole $role, public ?string $error = null)
    {
        $this->date = new \DateTimeImmutable();
    }

    public function toOpenAI(): array
    {
        return [
            'role' => $this->role->value,
            'content' => $this->text,
        ];
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function toPerplexica(): array
    {
        $roleValue = match ($this->role) {
            AiMessageRole::System => 'human',
            AiMessageRole::User => 'human',
            AiMessageRole::Assistant => 'assistant',
        };

        return [$roleValue, $this->text];
    }
}
