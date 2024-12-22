<?php

namespace App\Search;

use ACSEO\TypesenseBundle\Finder\TypesenseQuery;

class TypesenseTokenHandler
{


    public function handle(array $tokens): TypesenseQuery
    {
        $query = new TypesenseQuery($tokens['TEXT']??'', 'title,serie,extension,authors,tags,summary');

        $filterstring = [];
        foreach ($tokens as $class => $token) {
            if($class!=='TEXT'){
                $handler = new $class();

                if(!$handler instanceof TokenInterface){
                    throw new \Exception('Token handler must implement TokenInterface');
                }

                $filterstring[] = $handler->convertToQuery($token);

            }
        }
        $filterstring = array_filter($filterstring);
        $query->filterBy(implode(' && ', $filterstring));

        return $query;

    }
}