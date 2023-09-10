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

        $menu->setChildrenAttribute('class', 'nav flex-column');
        $menu->addChild('All Books', ['route' => 'app_homepage', ...$this->defaultAttr])->setExtra('icon', 'house-fill');
        $menu->addChild('Series', ['route' => 'app_groups', 'routeParameters' => ['type' => 'serie'], ...$this->defaultAttr])->setExtra('icon', 'list');
        $menu->addChild('Authors', ['route' => 'app_groups', 'routeParameters' => ['type' => 'authors'], ...$this->defaultAttr])->setExtra('icon', 'people-fill');
        $menu->addChild('Tags', ['route' => 'app_groups', 'routeParameters' => ['type' => 'tags'], ...$this->defaultAttr])->setExtra('icon', 'tags-fill');
        $menu->addChild('Publishers', ['route' => 'app_groups', 'routeParameters' => ['type' => 'publisher'], ...$this->defaultAttr])->setExtra('icon', 'tags-fill');

        $user = $this->security->getUser();

        if ($user instanceof User && $user->getShelves()->count() > 0) {
            $menu->addChild('shelves_divider', ['label' => 'Shelves'])->setExtra('divider', true);
            foreach ($user->getShelves() as $shelf) {
                /** @var Shelf $shelf */
                if ($shelf->getQueryString() !== null) {
                    $menu->addChild($shelf->getSlug(), ['label' => $shelf->getName(), 'route' => 'app_homepage', 'routeParameters' => $shelf->getQueryString(), ...$this->defaultAttr])
                        ->setExtra('icon', 'bookmark-fill');
                } else {
                    $menu->addChild($shelf->getSlug(), ['label' => $shelf->getName(), 'route' => 'app_shelf', 'routeParameters' => ['slug' => $shelf->getSlug()], ...$this->defaultAttr])
                        ->setExtra('icon', 'bookshelf');
                }
            }
        }

        return $menu;
    }
}
