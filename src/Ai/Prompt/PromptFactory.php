<?php

namespace App\Ai\Prompt;

use App\Config\ConfigValue;
use App\Entity\Book;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class PromptFactory
{
    public function __construct(
        private readonly ConfigValue $config,
        private readonly Security $security,
    ) {
    }

    public function getPrompt(string $class, Book $book, ?string $language = null): BookPromptInterface
    {
        $user = $this->security->getUser();
        if ($language === null) {
            $language = $book->getLanguage();
        }
        if ($language === null && $user instanceof User) {
            $language = $user->getLanguage();
        }
        if ($language === null) {
            $language = 'en';
        }

        /** @var BookPromptInterface $object */
        $object = new $class($book, $this->config, $language);
        $object->initialisePrompt();

        return $object;
    }
}
