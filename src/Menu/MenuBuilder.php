<?php

namespace App\Menu;

use App\Entity\Shelf;
use App\Entity\User;
use App\Service\FilteredBookUrlGenerator;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class MenuBuilder
{
    /**
     * @var array<string|mixed>
     */
    private array $defaultAttr = ['attributes' => ['class' => 'nav-item'], 'linkAttributes' => ['class' => 'nav-link icon-link'], 'icon' => 'fa-book'];

    /**
     * Add any other dependency you need...
     */
    public function __construct(private readonly FactoryInterface $factory, private readonly Security $security, private FilteredBookUrlGenerator $filteredBookUrlGenerator)
    {
    }

    /**
     * @param array<mixed> $options
     * @return ItemInterface
     */
    public function createMainMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return $menu;
        }

        $menu->setChildrenAttribute('class', 'nav flex-column');
        $menu->addChild('Home', ['route' => 'app_dashboard', ...$this->defaultAttr])->setExtra('icon', 'house-fill');

        if ($user->isDisplayAllBooks()) {
            $menu->addChild('All Books', ['route' => 'app_allbooks', ...$this->defaultAttr])->setExtra('icon', 'book-fill');
        }
        if ($user->isDisplayTimeline()) {
            $menu->addChild('Timeline', ['route' => 'app_timeline', ...$this->defaultAttr])->setExtra('icon', 'calendar2-week');
        }
        if ($user->isDisplaySeries()) {
            $menu->addChild('Series', ['route' => 'app_groups', 'routeParameters' => ['type' => 'serie'], ...$this->defaultAttr])->setExtra('icon', 'list');
        }
        if ($user->isDisplayAuthors()) {
            $menu->addChild('Authors', ['route' => 'app_groups', 'routeParameters' => ['type' => 'authors'], ...$this->defaultAttr])->setExtra('icon', 'people-fill');
        }
        if ($user->isDisplayTags()) {
            $menu->addChild('Tags', ['route' => 'app_groups', 'routeParameters' => ['type' => 'tags'], ...$this->defaultAttr])->setExtra('icon', 'tags-fill');
        }
        if ($user->isDisplayPublishers()) {
            $menu->addChild('Publishers', ['route' => 'app_groups', 'routeParameters' => ['type' => 'publisher'], ...$this->defaultAttr])->setExtra('icon', 'tags-fill');
        }

        $menu->addChild('Books to be finished', ['route' => 'app_started', ...$this->defaultAttr])->setExtra('icon', 'battery-half');

        if ($user->getShelves()->count() > 0) {
            $menu->addChild('shelves_divider', ['label' => 'Shelves'])->setExtra('divider', true);
            foreach ($user->getShelves() as $shelf) {
                /** @var Shelf $shelf */
                if ($shelf->getQueryString() !== null) {
                    $menu->addChild($shelf->getSlug(), ['label' => $shelf->getName(), 'route' => 'app_allbooks', 'routeParameters' => $shelf->getQueryString(), ...$this->defaultAttr])
                        ->setExtra('icon', 'bookmark-fill');
                } else {
                    $menu->addChild($shelf->getSlug(), ['label' => $shelf->getName(), 'route' => 'app_shelf', 'routeParameters' => ['slug' => $shelf->getSlug()], ...$this->defaultAttr])
                        ->setExtra('icon', 'bookshelf');
                }
            }
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $menu->addChild('admin_divider', ['label' => 'Admin'])->setExtra('divider', true);
            $menu->addChild('Admin', ['route' => 'app_user_index', ...$this->defaultAttr])->setExtra('icon', 'gear-fill');
            $menu->addChild('Kobos', ['route' => 'app_kobo_admin_index', ...$this->defaultAttr])->setExtra('icon', 'gear-fill');
            $menu->addChild('Add Books', ['route' => 'app_book_consume', ...$this->defaultAttr])->setExtra('icon', 'bookmark-plus-fill');

            $params = $this->filteredBookUrlGenerator->getParametersArray(['verified' => 'unverified', 'orderBy' => 'serieIndex-asc']);
            $menu->addChild('Not verified', ['route' => 'app_allbooks', ...$this->defaultAttr, 'routeParameters' => $params])->setExtra('icon', 'question-circle-fill');
        }

        $menu->addChild('profile_divider', ['label' => 'profile'])->setExtra('divider', true);
        $menu->addChild('My profile', ['route' => 'app_user_profile', ...$this->defaultAttr])->setExtra('icon', 'person-circle');
        $menu->addChild('Logout', ['route' => 'app_logout', ...$this->defaultAttr])->setExtra('icon', 'door-closed');

        return $menu;
    }
}
