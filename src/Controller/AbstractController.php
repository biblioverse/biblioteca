<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

abstract class AbstractController extends BaseAbstractController
{
    private function prefixView(string $view): string
    {
        if ($this->getUser() instanceof User) {
            /** @var User $user */
            $user = $this->getUser();
            $theme = $user->getTheme();
            if ($theme === 'default' || $theme === null) {
                return $view;
            }
            $twig = $this->container->get('twig');
            if (!$twig instanceof Environment) {
                return $view;
            }
            if ($twig->getLoader()->exists($theme.'/'.$view)) {
                $view = 'themes/'.$theme.'/'.$view;
            }
        }

        return $view;
    }

    private function doRenderView(string $view, ?string $block, array $parameters, string $method): string
    {
        $view = $this->prefixView($view);
        if (!$this->container->has('twig')) {
            throw new \LogicException(sprintf('You cannot use the "%s" method if the Twig Bundle is not available. Try running "composer require symfony/twig-bundle".', $method));
        }

        foreach ($parameters as $k => $v) {
            if ($v instanceof FormInterface) {
                $parameters[$k] = $v->createView();
            }
        }

        $twig = $this->container->get('twig');
        if (!$twig instanceof Environment) {
            return $view;
        }

        if (null !== $block) {
            return $twig->load($view)->renderBlock($block, $parameters);
        }

        return $twig->render($view, $parameters);
    }

    protected function renderView(string $view, array $parameters = []): string
    {
        return $this->doRenderView($view, null, $parameters, __FUNCTION__);
    }

    protected function renderBlockView(string $view, string $block, array $parameters = []): string
    {
        return $this->doRenderView($view, $block, $parameters, __FUNCTION__);
    }

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        return $this->doRender($view, null, $parameters, $response, __FUNCTION__);
    }

    protected function renderBlock(string $view, string $block, array $parameters = [], ?Response $response = null): Response
    {
        return $this->doRender($view, $block, $parameters, $response, __FUNCTION__);
    }

    private function doRender(string $view, ?string $block, array $parameters, ?Response $response, string $method): Response
    {
        $view = $this->prefixView($view);
        $content = $this->doRenderView($view, $block, $parameters, $method);
        $response ??= new Response();

        if (200 === $response->getStatusCode()) {
            foreach ($parameters as $v) {
                if ($v instanceof FormInterface && $v->isSubmitted() && !$v->isValid()) {
                    $response->setStatusCode(422);
                    break;
                }
            }
        }

        $response->setContent($content);

        return $response;
    }
}
