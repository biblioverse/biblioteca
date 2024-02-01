<?php

namespace App\Tests\Contraints;

use PHPUnit\Framework\Constraint\Constraint;

class ArrayHasNestedKey extends Constraint
{
    public function __construct(private readonly string $path)
    {
    }

    public function matches($other): bool{
        try{
            $this->test($other);
        }catch (\InvalidArgumentException $e){
            return false;
        }
        return true;
    }

    /**
     * Validate the constraint and throw an exception for the first invalid value encountered.
     * The exception message complete "failing asserting that...".
     * @param array|mixed $other
     * @return void
     */
    public function test($other): void
    {
        $evaluated = "";
        $paths = explode('.', $this->path);
        foreach($paths as $path){
            assert(is_array($other), sprintf('Path %s should contain an array', $evaluated));
            if(!isset($other[$path])) {
                throw new \InvalidArgumentException(sprintf('Path %s exists', $this->path));
            }
            $evaluated .= sprintf('.%s', $path);
            $evaluated = ltrim($evaluated, '.');
            $other = $other[$path];
        }
    }

    public function toString(): string
    {
        return 'has nested key ' . $this->path;
    }
}