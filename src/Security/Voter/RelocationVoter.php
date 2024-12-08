<?php

namespace App\Security\Voter;

use App\Entity\Book;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class RelocationVoter extends Voter
{
    public const RELOCATE = 'RELOCATE';

    public function __construct(
        #[Autowire(param: 'ALLOW_BOOK_RELOCATION')]
        private bool $allowBookRelocation,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::RELOCATE && $subject instanceof Book;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
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
