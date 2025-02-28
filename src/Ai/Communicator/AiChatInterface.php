<?php

namespace App\Ai\Communicator;

use App\Ai\Message;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.ai_communicator', ['priority' => 20])]
interface AiChatInterface extends AiCommunicatorInterface
{
    /**
     * @param Message[] $messages
     */
    public function chat(array $messages): string;
}
