<?php
namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

final class MenuBuilder
{
    private FactoryInterface $factory;

    /**
     * @var array <string, mixed>
     */
    private array $defaultAttr = ['attributes' => ['class' => 'nav-item'],'linkAttributes'=>['class'=>'nav-link'], 'icon'=>'fa-book'];

    /**
     * Add any other dependency you need...
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param array<mixed> $options
     * @return ItemInterface
     */
    public function createMainMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $menu->setChildrenAttribute('class', 'nav flex-column');
        $menu->addChild('Home', ['route' => 'app_homepage', ...$this->defaultAttr]);
        $menu->addChild('Favorites', ['route' => 'app_favorites', ...$this->defaultAttr]);
        $menu->addChild('Read', ['route' => 'app_read', 'routeParameters' => ['read' => 1], ...$this->defaultAttr]);
        $menu->addChild('Not read', ['route' => 'app_read', 'routeParameters' => ['read' => 0], ...$this->defaultAttr]);
        $menu->addChild('Series', ['route' => 'app_series', ...$this->defaultAttr]);
        $menu->addChild('Authors', ['route' => 'app_authors', ...$this->defaultAttr]);
        $menu->addChild('Settings',['route' => 'admin', ...$this->defaultAttr ]);

        return $menu;
    }
}