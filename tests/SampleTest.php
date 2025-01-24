<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SampleTest extends KernelTestCase
{
    public function testSample(): void
    {
        self::bootKernel();
        // @phpstan-ignore-next-line
        self::assertTrue(true);
    }
}
