<?php

namespace App\Enum;

enum ReadingList: string
{

    case ToRead = 'rl-to-read';
    case Ignored = 'rl-ignored';
    case NotDefined = 'rl-undefined';

}
