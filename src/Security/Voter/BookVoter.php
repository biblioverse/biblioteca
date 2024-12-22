<?php

namespace App\Security\Voter;

use App\Entity\Book;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BookVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const VIEW = 'VIEW';

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW], true)
            && $subject instanceof Book;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Book) {
            return false;
        }

        return match ($attribute) {
            self::EDIT => in_array('ROLE_ADMIN', $user->getRoles(), true),
            self::VIEW => $user->getMaxAgeCategory() === null || $subject->getAgeCategory() <= $user->getMaxAgeCategory(),
            default => false,
        };
    }
}
