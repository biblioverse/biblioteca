<?php

namespace App\Search;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class QueryTokenizer
{

    public function __construct(
        #[AutowireIterator('app.search_token')]
        private readonly iterable $handlers,
    ) {
    }

    public function tokenize(string $initialQuery): array
    {
        $remainingQuery = $initialQuery;

        $tokens = [];

        foreach ($this->handlers as $token) {
            if( ! $token instanceof TokenInterface) {
                throw new \InvalidArgumentException('Token must implement TokenInterface');
            }
            $matches=[];

            if (preg_match_all($token->getRegex(), $remainingQuery, $matches)) {

                $tokens[$token::class] = reset($matches);

                foreach ($matches as $match) {
                    $remainingQuery = str_replace($match, '', $remainingQuery );
                }
            }
        }

        if(trim($remainingQuery) !== '') {
            $tokens['TEXT'] =  trim($remainingQuery);
            $tokens['TEXT'] =  trim($tokens['TEXT'],',');
        }


        return $tokens;
    }
}