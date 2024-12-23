<?php

namespace App\Search;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class QueryTokenizer
{
    /**
     * @param array<TokenInterface> $handlers
     */
    public function __construct(
        #[AutowireIterator('app.search_token')]
        private iterable $handlers,
    ) {
    }

    public function tokenize(string $initialQuery): array
    {
        $remainingQuery = $initialQuery;

        $tokens = [];

        foreach ($this->handlers as $token) {
            $matches = [];

            if (preg_match_all($token->getRegex(), $remainingQuery, $matches) !== false) {
                $tokens[$token::class] = reset($matches);

                foreach ($matches as $match) {
                    $remainingQuery = str_replace($match, '', $remainingQuery);
                }
            }
        }

        if (trim($remainingQuery) !== '') {
            $tokens['TEXT'] = ltrim($remainingQuery);
        }

        return $tokens;
    }
}
