<?php

namespace App\Security\Voter;

use App\Ai\Communicator\AiAction;
use App\Ai\Communicator\AiCommunicatorInterface;
use App\Ai\Communicator\CommunicatorDefiner;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class AiFeaturesVoter extends Voter
{
    public const string USE = 'USE_AI_FEATURES';

    public function __construct(
        private readonly CommunicatorDefiner $aiCommunicator,
    ) {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return $attribute === self::USE && $subject instanceof AiAction;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (!$subject instanceof AiAction) {
            return false;
        }

        return $this->aiCommunicator->getCommunicator($subject) instanceof AiCommunicatorInterface;
    }
}
