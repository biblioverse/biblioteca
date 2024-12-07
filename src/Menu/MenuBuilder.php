<?php

namespace App\Menu;

use App\Entity\Shelf;
use App\Entity\User;
use App\Service\FilteredBookUrlGenerator;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final class MenuBuilder
{
    /**
     * @var array<string|mixed>
     */
    private array $defaultAttr = ['attributes' => ['class' => 'nav-item'], 'linkAttributes' => ['class' => 'nav-link icon-link'], 'icon' => 'fa-book'];

    /**
     * Add any other dependency you need...
     */
    public function __construct(private readonly FactoryInterface $factory, private readonly Security $security, private readonly FilteredBookUrlGenerator $filteredBookUrlGenerator, private readonly RequestStack $requestStack)
    {
    }

    public function isBookRoute(): bool
    {
        // Get the current request
        $currentRequest = $this->requestStack->getCurrentRequest();

        return $currentRequest?->attributes->get('_route') === 'app_book';

        // Continue with the process to extract the route name
    }

    public function getBookRouteParams(): array
    {
        // Get the current request
        $currentRequest = $this->requestStack->getCurrentRequest();

        return [
            'slug' => $currentRequest?->attributes->get('slug'),
            'book' => $currentRequest?->attributes->get('book'),
        ];
        // Continue with the process to extract the route name
    }

    public function createMainMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $menu;
        }
        $menu->setChildrenAttribute('class', 'nav flex-column');
        $books = $menu->addChild('books_divider', ['label' => 'menu.books'])->setExtra('divider', true);

        $books->addChild('menu.home', ['route' => 'app_dashboard', ...$this->defaultAttr])->setExtra('icon', 'house-fill');
        if ($user->isDisplayAllBooks()) {
            $allBooks = $books->addChild('menu.allbooks', ['route' => 'app_allbooks', ...$this->defaultAttr])->setExtra('icon', 'book-fill');

            if ($this->isBookRoute()) {
                $params = $this->getBookRouteParams();
                $allBooks->addChild('book', ['route' => 'app_book', ...$this->defaultAttr, 'routeParameters' => $params])->setDisplay(false);
            }
        }
        if ($user->isDisplaySeries()) {
            $books->addChild('menu.series', ['route' => 'app_groups', 'routeParameters' => ['type' => 'serie'], ...$this->defaultAttr])->setExtra('icon', 'list');
        }
        if ($user->isDisplayAuthors()) {
            $books->addChild('menu.authors', ['route' => 'app_groups', 'routeParameters' => ['type' => 'authors'], ...$this->defaultAttr])->setExtra('icon', 'people-fill');
        }
        if ($user->isDisplayTags()) {
            $books->addChild('menu.tags', ['route' => 'app_groups', 'routeParameters' => ['type' => 'tags'], ...$this->defaultAttr])->setExtra('icon', 'tags-fill');
        }
        if ($user->isDisplayPublishers()) {
            $books->addChild('menu.publishers', ['route' => 'app_groups', 'routeParameters' => ['type' => 'publisher'], ...$this->defaultAttr])->setExtra('icon', 'tags-fill');
        }
        $profile = $menu->addChild('profile_divider', ['label' => $user->getUsername()])->setExtra('divider', true);
        $profile->addChild('menu.readinglist', ['route' => 'app_readinglist', ...$this->defaultAttr])->setExtra('icon', 'list-task');
        if ($user->isDisplayTimeline()) {
            $profile->addChild('menu.timeline', ['route' => 'app_timeline', ...$this->defaultAttr])->setExtra('icon', 'calendar2-week');
        }
        if ($user->isUseKoboDevices()) {
            $profile->addChild('menu.kobodevices', ['route' => 'app_kobodevice_user_index', ...$this->defaultAttr])->setExtra('icon', 'gear-fill');
        }

        $profile->addChild('menu.profile', ['route' => 'app_user_profile', ...$this->defaultAttr])->setExtra('icon', 'person-circle');
        $profile->addChild('menu.logout', ['route' => 'app_logout', ...$this->defaultAttr])->setExtra('icon', 'door-closed');

        $shelves = $menu->addChild('shelves_divider', ['label' => 'menu.shelves'])->setExtra('divider', true);
        if ($user->getShelves()->count() > 0) {
            foreach ($user->getShelves() as $shelf) {
                /** @var Shelf $shelf */
                if ($shelf->getQueryString() !== null) {
                    $shelves->addChild($shelf->getSlug(), ['label' => $shelf->getName(), 'route' => 'app_allbooks', 'routeParameters' => $shelf->getQueryString(), ...$this->defaultAttr])
                        ->setExtra('icon', 'bookmark-fill');
                } else {
                    $shelves->addChild($shelf->getSlug(), ['label' => $shelf->getName(), 'route' => 'app_shelf', 'routeParameters' => ['slug' => $shelf->getSlug()], ...$this->defaultAttr])
                        ->setExtra('icon', 'bookshelf');
                }
            }
        }
        $shelves->addChild('menu.editshelves', ['route' => 'app_shelf_crud_index', ...$this->defaultAttr])->setExtra('icon', 'building-fill-gear');

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $admin = $menu->addChild('admin_divider', ['label' => 'menu.admin'])->setExtra('divider', true);

            $admin->addChild('menu.useradmin', ['route' => 'app_user_index', ...$this->defaultAttr])->setExtra('icon', 'gear-fill');
            $admin->addChild('menu.addbooks', ['route' => 'app_book_consume', ...$this->defaultAttr])->setExtra('icon', 'bookmark-plus-fill');
            $admin->addChild('menu.upload', ['route' => 'app_book_upload_consume', ...$this->defaultAttr])->setExtra('icon', 'bookmark-plus-fill');

            $params = $this->filteredBookUrlGenerator->getParametersArray(['verified' => 'unverified', 'orderBy' => 'serieIndex-asc']);
            $admin->addChild('menu.notverified', ['route' => 'app_allbooks', ...$this->defaultAttr, 'routeParameters' => $params])->setExtra('icon', 'question-circle-fill');
        }

        return $menu;
    }
}
