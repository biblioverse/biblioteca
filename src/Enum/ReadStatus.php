<?php

namespace App\Enum;

enum ReadStatus: string
{

    case NotStarted = 'rs-not-started';
    case Started = 'rs-started';
    case Finished = 'rs-finished';

}
