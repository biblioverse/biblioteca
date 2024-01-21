<?php

namespace App\Menu;

use App\Entity\Shelf;
use App\Entity\User;
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
    public function __construct(private readonly FactoryInterface $factory, private readonly Security $security)
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

        $menu->addChild('profile_divider', ['label' => 'profile'])->setExtra('divider', true);

        $menu->addChild('My profile', ['route' => 'app_user_profile', ...$this->defaultAttr])->setExtra('icon', 'person-circle');
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $menu->addChild('Admin', ['route' => 'app_user_index', ...$this->defaultAttr])->setExtra('icon', 'gear-fill');
        }
        $menu->addChild('Logout', ['route' => 'app_logout', ...$this->defaultAttr])->setExtra('icon', 'door-closed');

        return $menu;
    }
}
