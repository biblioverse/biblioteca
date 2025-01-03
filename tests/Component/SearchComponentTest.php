<?php

namespace App\Tests\Component;

use App\DataFixtures\UserFixture;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Twig\Components\Search;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

class SearchComponentTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    public function getLiveComponent(TestLiveComponent $testComponent): void
    {
        $testComponent->set('advanced', true);
    }

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testCanRenderAndInteract(): void
    {
        $testComponent = $this->createLiveComponent(
            name: Search::class,
        );

        $userRepository = static::getContainer()->get(UserRepository::class);

        if (!$userRepository instanceof UserRepository) {
            throw new \RuntimeException('User repository is not an instance of UserRepository');
        }

        $user = $userRepository->findOneBy(['username' => UserFixture::USER_USERNAME]);

        if (!$user instanceof User) {
            throw new \RuntimeException('User not found');
        }

        $testComponent->actingAs($user);

        self::assertStringContainsString('Advanced filters', $testComponent->render());

        $testComponent->set('query', '*');
        self::assertStringContainsString('results', $testComponent->render());

        self::assertStringNotContainsString('id="search-filters"', $testComponent->render());
        $this->getLiveComponent($testComponent);
        self::assertStringContainsString('id="search-filters"', $testComponent->render());
        $testComponent->set('advanced', false);

        $testComponent->set('query', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        self::assertStringContainsString('0 results', $testComponent->render());

        $testComponent->set('query', 'homer');
        self::assertStringContainsString('1 result', $testComponent->render());

        $testComponent->set('filterQuery', 'homer');
        self::assertStringContainsString('id="search-filters"', $testComponent->render());
        self::assertStringContainsString('Could not parse the filter query', $testComponent->render());
        $testComponent->set('filterQuery', 'authors:="homer"');
        self::assertStringContainsString('1 result', $testComponent->render());

        $testComponent->set('filterQuery', '');

        $testComponent->call('addFilter', ['value' => 'authors:="homer"']);
        self::assertStringContainsString('1 result', $testComponent->render());

        $testComponent->set('filterQuery', '');
        $testComponent->set('query', '*');
        $testComponent->set('shelfname', 'test-dynamic');
        $testComponent->render();
        $testComponent->call('save');
    }
}
