<?php

namespace App\Ai\Communicator;

enum AiAction: string
{
    case Summary = 'AI_SUMMARIZATION_MODEL';
    case Tags = 'AI_TAG_MODEL';
    case Search = 'AI_SEARCH_MODEL';
    case Assistant = 'AI_ASSISTANT_MODEL';
}
