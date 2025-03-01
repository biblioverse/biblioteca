<?php

namespace App\Menu;

use App\Entity\Shelf;
use App\Entity\User;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final class MenuBuilder
{
    /**
     * @var array<string|mixed>
     */
    private array $defaultAttr = [
        'attributes' => ['class' => 'MenuItem'],
        'linkAttributes' => ['class' => 'MenuLink'],
    ];

    /**
     * Add any other dependency you need...
     */
    public function __construct(private readonly FactoryInterface $factory, private readonly Security $security, private readonly RequestStack $requestStack)
    {
    }

    public function isBookRoute(): bool
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        return $currentRequest?->attributes->get('_route') === 'app_book';
    }

    public function isSerieRoute(): bool
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        return $currentRequest?->attributes->get('_route') === 'app_serie_detail';
    }

    public function isAuthorRoute(): bool
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        return $currentRequest?->attributes->get('_route') === 'app_author_detail';
    }

    public function getRouteParams(array $params): array
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        $extractedParams = [];
        foreach ($params as $param) {
            $extractedParams[$param] = $currentRequest?->attributes->get($param);
        }

        return $extractedParams;
    }

    public function createMainMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $menu;
        }
        $books = $menu->addChild('books_divider', ['label' => 'menu.books'])
            ->setExtra('header', true)
            ->setExtra('icon', 'clipboard-data');

        $books->addChild('menu.home', ['route' => 'app_dashboard', ...$this->defaultAttr])->setExtra('icon', 'house-fill');
        if ($user->isDisplayAllBooks()) {
            $allBooks = $books->addChild('menu.allbooks', ['route' => 'app_allbooks', ...$this->defaultAttr])->setExtra('icon', 'book-fill');

            if ($this->isBookRoute()) {
                $params = $this->getRouteParams(['slug', 'book']);
                $allBooks->addChild('book', ['route' => 'app_book', 'routeParameters' => $params])->setDisplay(false);
            }
        }
        if ($user->isDisplaySeries()) {
            $series = $books->addChild('menu.series', ['route' => 'app_groups', 'routeParameters' => ['type' => 'serie'], ...$this->defaultAttr])->setExtra('icon', 'list');

            if ($this->isSerieRoute()) {
                $params = $this->getRouteParams(['name']);
                $series->addChild('serie', ['route' => 'app_serie_detail', 'routeParameters' => $params])->setDisplay(false);
            }
        }
        if ($user->isDisplayAuthors()) {
            $author = $books->addChild('menu.authors', ['route' => 'app_groups', 'routeParameters' => ['type' => 'authors'], ...$this->defaultAttr])->setExtra('icon', 'feather');

            if ($this->isAuthorRoute()) {
                $params = $this->getRouteParams(['name']);
                $author->addChild('serie', ['route' => 'app_author_detail', 'routeParameters' => $params])->setDisplay(false);
            }
        }
        if ($user->isDisplayTags()) {
            $books->addChild('menu.tags', ['route' => 'app_groups', 'routeParameters' => ['type' => 'tags'], ...$this->defaultAttr])->setExtra('icon', 'tags-fill');
        }
        if ($user->isDisplayPublishers()) {
            $books->addChild('menu.publishers', ['route' => 'app_groups', 'routeParameters' => ['type' => 'publisher'], ...$this->defaultAttr])->setExtra('icon', 'tags-fill');
        }

        $profile = $menu->addChild('profile_divider', ['label' => $user->getUsername()])
            ->setExtra('header', true)
            ->setExtra('translation_domain', false)
            ->setExtra('icon', 'person-circle');
        $profile->addChild('menu.readinglist', ['route' => 'app_readinglist', ...$this->defaultAttr])->setExtra('icon', 'list-task');
        if ($user->isDisplayTimeline()) {
            $profile->addChild('menu.timeline', ['route' => 'app_timeline', ...$this->defaultAttr])->setExtra('icon', 'calendar2-week');
        }

        $profile->addChild('menu.profile', ['route' => 'app_user_profile', ...$this->defaultAttr])->setExtra('icon', 'person-circle');
        $profile->addChild('menu.logout', ['route' => 'app_logout', ...$this->defaultAttr])->setExtra('icon', 'door-closed');

        $shelves = $menu->addChild('shelves_divider', ['label' => 'menu.shelves'])
            ->setExtra('header', true)
            ->setExtra('icon', 'bookshelf');
        if ($user->getShelves()->count() > 0) {
            foreach ($user->getShelves() as $shelf) {
                /** @var Shelf $shelf */
                if ($shelf->isDynamic()) {
                    $shelves->addChild($shelf->getSlug(), ['label' => $shelf->getName(), 'route' => 'app_allbooks', 'routeParameters' => ['page' => 1, 'filterQuery' => $shelf->getQueryFilter(), 'orderQuery' => $shelf->getQueryOrder(), 'query' => $shelf->getQueryString()], ...$this->defaultAttr])
                        ->setExtra('icon', 'bookmark-fill')->setExtra('translation_domain', false);
                } else {
                    $shelves->addChild($shelf->getSlug(), ['label' => $shelf->getName(), 'route' => 'app_shelf', 'routeParameters' => ['slug' => $shelf->getSlug()], ...$this->defaultAttr])
                        ->setExtra('icon', 'bookshelf')->setExtra('translation_domain', false);
                }
            }
        }
        $shelves->addChild('menu.editshelves', ['route' => 'app_shelf_crud_index', ...$this->defaultAttr])->setExtra('icon', 'building-fill-gear');

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $admin = $menu->addChild('admin_divider', ['label' => 'menu.admin'])
                ->setExtra('header', true)
                ->setExtra('icon', 'gear-fill');

            $admin->addChild('menu.useradmin', ['route' => 'app_user_index', ...$this->defaultAttr])->setExtra('icon', 'gear-fill');
            $admin->addChild('menu.addbooks', ['route' => 'app_book_consume', ...$this->defaultAttr])->setExtra('icon', 'bookmark-plus-fill');
            $admin->addChild('menu.kobodevices', ['route' => 'app_kobodevice_user_index', ...$this->defaultAttr])->setExtra('icon', 'gear-fill');
            $admin->addChild('menu.instanceconfig', ['route' => 'app_instance_configuration_index', ...$this->defaultAttr])->setExtra('icon', 'gear-fill');
            $admin->addChild('menu.aimodels', ['route' => 'app_ai_model_index', ...$this->defaultAttr])->setExtra('icon', 'magic');
            $admin->addChild('menu.notverified', ['route' => 'app_notverified', ...$this->defaultAttr])->setExtra('icon', 'question-circle-fill');
        }

        return $menu;
    }
}
