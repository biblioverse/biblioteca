<?php

namespace App\Ai\Prompt;

use App\Config\ConfigValue;
use App\Entity\Book;
use App\Entity\User;

class PromptFactory
{
    public function __construct(private readonly ConfigValue $config)
    {
    }

    public function getPrompt(string $class, Book $book, ?User $user): BookPromptInterface
    {
        /** @var BookPromptInterface $object */
        $object = new $class($book, $user, $this->config);
        $object->initialisePrompt();

        return $object;
    }
}
