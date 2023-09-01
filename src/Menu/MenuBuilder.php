<?php
namespace App\Menu;

use App\Entity\User;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class MenuBuilder
{
    /**
     * @var array <string, mixed>
     */
    private array $defaultAttr = ['attributes' => ['class' => 'nav-item'],'linkAttributes'=>['class'=>'nav-link icon-link'], 'icon'=>'fa-book'];

    /**
     * Add any other dependency you need...
     */
    public function __construct(private FactoryInterface $factory, private Security $security)
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
        $menu->addChild('Home', ['route' => 'app_homepage', ...$this->defaultAttr])->setExtra('icon','house-fill');
        $menu->addChild('Favorites', ['route' => 'app_favorites', ...$this->defaultAttr])->setExtra('icon','heart-fill');
        $menu->addChild('Read', ['route' => 'app_read', 'routeParameters' => ['read' => 1], ...$this->defaultAttr])->setExtra('icon','journal-check');
        $menu->addChild('Not read', ['route' => 'app_read', 'routeParameters' => ['read' => 0], ...$this->defaultAttr])->setExtra('icon','journal');
        $menu->addChild('Series', ['route' => 'app_serie', ...$this->defaultAttr])->setExtra('icon','list');
        $menu->addChild('Authors', ['route' => 'app_authors', ...$this->defaultAttr])->setExtra('icon','people-fill');
        $menu->addChild('Unverified', ['route' => 'app_unverified', ...$this->defaultAttr])->setExtra('icon','question-circle-fill');
        $menu->addChild('setting_divider',['label'=>'Others'])->setExtra('divider',true);
        $menu->addChild('Settings',['route' => 'admin', ...$this->defaultAttr ])->setExtra('icon','gear-fill');

        $menu->addChild('shelves_divider',['label'=>'Shelves'])->setExtra('divider',true);
        $user = $this->security->getUser();
        if($user instanceof User){
            foreach ($user->getShelves() as $shelf){
                $menu->addChild($shelf->getSlug(), ['label'=>$shelf->getName(),'route' => 'app_shelf', 'routeParameters' => ['slug' => $shelf->getSlug()], ...$this->defaultAttr])
                ->setExtra('icon','bookshelf');
            }
        }



        return $menu;
    }
}