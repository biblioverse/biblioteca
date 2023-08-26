<?php
namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

final class MenuBuilder
{
    private FactoryInterface $factory;

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

        $menu->addChild('Home', ['route' => 'app_homepage']);
        $menu->addChild('Series', ['route' => 'app_series']);
        $menu->addChild('Authors', ['route' => 'app_authors']);
        $menu->addChild('Other',);
        $menu->addChild('Settings',['route' => 'admin']);
        // ... add more children

        return $menu;
    }
}