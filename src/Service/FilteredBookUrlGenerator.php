<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use App\Form\BookFilterType;
use Symfony\Component\HttpFoundation\RequestStack;

class FilteredBookUrlGenerator
{
    public const FIELDS_DEFAULT_VALUE = [
        'title' => '',
        'authors' => [],
        'authorsNot' => [],
        'serieIndexLTE' => '',
        'serieIndexGTE' => '',
        'tags' => [],
        'serie' => '',
        'publisher' => '',
        'read' => '',
        'picture' => '',
        'favorite' => '',
        'extension' => '',
        'verified' => '',
        'orderBy' => '',
        'age' => [],
        'submit' => '',
    ];

    public function __construct(private RequestStack $request)
    {
    }

    /**
     * @return array<string[]>
     */
    public function getParametersArray(array $values): array
    {
        $params = self::FIELDS_DEFAULT_VALUE;
        foreach ($values as $key => $value) {
            if (!array_key_exists($key, self::FIELDS_DEFAULT_VALUE)) {
                throw new \RuntimeException('Invalid key '.$key);
            }
            if (is_array($value)) {
                $value = implode(BookFilterType::AUTOCOMPLETE_DELIMITER, $value);
            }
            $params[$key] = $value;
        }

        return $params;
    }

    public function getParametersArrayForCurrent(bool $onlyModified = false): array
    {
        $request = $this->request->getMainRequest();
        if (!$request instanceof Request) {
            return self::FIELDS_DEFAULT_VALUE;
        }
        $params = [];

        $queryParams = $request->query->all();

        foreach (self::FIELDS_DEFAULT_VALUE as $key => $value) {
            if (array_key_exists($key, $queryParams)) {
                $value = $queryParams[$key];
                if (($key === 'authors' || $key === 'authorsNot' || $key === 'tags' || $key === 'age') && is_string($value)) {
                    $value = array_filter(explode(BookFilterType::AUTOCOMPLETE_DELIMITER, $value));
                }
            }

            if ($onlyModified && $value === self::FIELDS_DEFAULT_VALUE[$key]) {
                continue;
            }
            $params[$key] = $value;
        }

        return $params;
    }
}
