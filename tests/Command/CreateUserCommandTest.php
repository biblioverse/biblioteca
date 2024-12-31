<?php

namespace App\Tests\Command;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateUserCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:create-admin-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'username' => 'test',
            'password' => 'test',
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('User created', $output);

        $userRepository = static::getContainer()->get(UserRepository::class);

        if (!$userRepository instanceof UserRepository) {
            throw new \RuntimeException('User repository is not an instance of UserRepository');
        }

        $user = $userRepository->findOneBy(['username' => 'test']);

        self::assertNotNull($user);

        $userRepository->remove($user, true);
    }
}
