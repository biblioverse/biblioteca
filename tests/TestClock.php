<?php
namespace App\Tests;
use Psr\Clock\ClockInterface;

class TestClock implements ClockInterface
{

    private static ?\DateTimeImmutable $now = null;

    public function now(): \DateTimeImmutable
    {
        return self::$now ?? new \DateTimeImmutable();
    }

    public function setTime(?\DateTimeImmutable $now): self
    {
        self::$now = $now;

        return $this;
    }
}