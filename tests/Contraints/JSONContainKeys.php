<?php

namespace App\Tests\Contraints;

use PHPUnit\Framework\Constraint\Constraint;

class JSONContainKeys extends Constraint
{

    public function __construct(private readonly array $keys, private readonly ?string $path = null)
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
        if(false === is_iterable($other)){
            throw new \InvalidArgumentException('JSON is iterable');
        }

        if($this->path !== null && is_array($other)){
            if(! array_key_exists($this->path, $other)) {
                throw new \InvalidArgumentException(sprintf('Path %s exists', $this->path));
            }

            $data = $other[$this->path];
        }

        foreach($this->keys as $key){
            if(!isset($data[$key])) {
                $keyLabel = $this->path !== null ? sprintf('%s.%s', $this->path, $key) : $key;
                throw new \InvalidArgumentException(sprintf('Key %s exists', $keyLabel));
            }

        }
    }

    public function failureDescription($other): string
    {
        try{
            $this->test($other);
            return 'JSON contains all expected keys';
        }catch (\InvalidArgumentException $e){
            return $e->getMessage();
        }
    }

    public function toString(): string
    {
        if($this->path !== null){
            return sprintf('JSON at path %s contains all expected keys: %s', $this->path, implode(', ', $this->keys));
        }
        return sprintf('JSON contains all expected keys: %s', implode(', ', $this->keys));
    }
}