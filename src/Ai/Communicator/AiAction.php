<?php

namespace App\Ai\Communicator;

enum AiAction: string
{
    case Search = 'AI_SEARCH_MODEL';
    case Assistant = 'AI_ASSISTANT_MODEL';
    case Context = 'AI_CONTEXT_MODEL';
}
