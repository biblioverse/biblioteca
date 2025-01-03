<?php

namespace App\Service;

class FilteredBookUrlGenerator
{
    public function getParametersArray(array $values): array
    {
        $fullQuery = '';
        foreach ($values as $key => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }
            foreach ($value as $v) {
                $fullQuery .= $key.':=`'.$v.'` ';
            }
        }

        return ['filterQuery' => $fullQuery];
    }
}
