<?php

namespace App\Security\Voter;

use App\Entity\BookInteraction;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class BookInteractionVoter extends Voter
{
    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof BookInteraction;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (!$subject instanceof BookInteraction) {
            return false;
        }

        return $subject->getUser() === $user;
    }
}
