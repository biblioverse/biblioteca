<?php

namespace App\Security\Voter;

use App\Entity\Book;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class RelocationVoter extends Voter
{
    public const string RELOCATE = 'RELOCATE';

    public function __construct(
        #[Autowire(param: 'ALLOW_BOOK_RELOCATION')]
        private readonly bool $allowBookRelocation,
    ) {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::RELOCATE && $subject instanceof Book;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if ($this->allowBookRelocation && PHP_SAPI === 'cli') {
            return true;
        }
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (!$this->allowBookRelocation) {
            return false;
        }

        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
