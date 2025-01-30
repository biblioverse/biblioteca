<?php

namespace App\Ai\Prompt;

use App\Config\ConfigValue;
use App\Entity\Book;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PromptFactory
{
    public function __construct(
        private readonly ConfigValue $config,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getPrompt(string $class, Book $book, ?User $user): BookPromptInterface
    {
        /** @var BookPromptInterface $object */
        $object = new $class($book, $user, $this->config, $this->getLanguage($user));
        $object->initialisePrompt();

        return $object;
    }

    private function getLanguage(?User $user): string
    {
        $language = 'en';
        if ($this->requestStack->getCurrentRequest() instanceof Request) {
            $language = $this->requestStack->getCurrentRequest()->getLocale();
        }

        return $user?->getLanguage() ?? $language;
    }
}
