<?php

namespace App\Security\Voter;

use App\Ai\AiCommunicatorInterface;
use App\Ai\CommunicatorDefiner;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class AiFeaturesVoter extends Voter
{
    public const USE = 'USE_AI_FEATURES';

    public function __construct(
        private readonly CommunicatorDefiner $aiCommunicator,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return $attribute === self::USE;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return false;
        }

        return $this->aiCommunicator->getCommunicator() instanceof AiCommunicatorInterface;
    }
}
