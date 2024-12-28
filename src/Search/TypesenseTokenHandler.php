<?php

namespace App\Search;

use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class TypesenseTokenHandler
{
    public function __construct(private readonly Security $security)
    {
    }

    public function handle(array $tokens): TypesenseQuery
    {
        $query = new TypesenseQuery($tokens['TEXT'] ?? '', 'title,serie,extension,authors,tags,summary');

        $filterstring = [];
        $orderString = [];
        foreach ($tokens as $class => $token) {
            if ($class !== 'TEXT') {
                $handler = new $class();
                $user = $this->security->getUser();
                if (!$user instanceof User) {
                    throw new \Exception('User must be an instance of App\Entity\User');
                }

                if (!$handler instanceof TokenInterface) {
                    throw new \Exception('Token handler must implement TokenInterface');
                }
                $handler->setUser($user);

                $handler->parseTokens($token);
                $filterstring[] = $handler->getFilterQuery();
                $orderString[] = $handler->getOrderQuery();
            }
        }
        $filterstring = array_filter($filterstring);
        $query->filterBy(implode(' && ', $filterstring));
        $orderString = array_filter($orderString);
        $query->sortBy(implode(',', $orderString));

        return $query;
    }
}
