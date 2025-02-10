<?php

namespace App\Ai;

use App\Enum\AiMessageRole;

class Message
{
    public ?array $suggestions = null;
    public \DateTimeImmutable $date;

    public function __construct(public string $text, public AiMessageRole $role)
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
}
