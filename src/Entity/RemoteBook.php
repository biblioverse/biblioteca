<?php

namespace App\Entity;

class RemoteBook
{
    public function __construct(
        public string $path,
    ) {
    }
}
