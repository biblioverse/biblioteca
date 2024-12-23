<?php

namespace App\Service;

class FilteredBookUrlGenerator
{

    /**
     * @return array<string[]>
     */
    public function getParametersArray(array $values): array
    {
        $fullQuery = '';
        foreach ($values as $key => $value) {

            if(!is_array($value)){
                $value = [$value];
            }
            foreach ($value as $v) {
                $fullQuery.= $key.':"'.$v.'" ';
            }
        }

        return ['fullQuery' => $fullQuery];
    }
}
