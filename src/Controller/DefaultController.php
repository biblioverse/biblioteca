<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Sonata\SeoBundle\Seo\SeoPageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        return $this->forward('App\Controller\DefaultController::pages', ['page' => 1]);
    }

    #[Route('/page/{page?1<\d+>}', name: 'app_pages')]
    public function pages(EntityManagerInterface $manager, PaginatorInterface $paginator, SeoPageInterface $seo, $page = 1): Response
    {

        $seo->addTitlePrefix('Page ' . (int)$page);

        if ((int)$page === 1) {
            $seo->setLinkCanonical($this->generateUrl('app_homepage'));
            $pinned = $manager->getRepository(Article::class)->findBy(['pinned' => true, 'published' => true], ['created' => 'desc']);
            if (count($pinned) < 2) {
                $others = $manager->getRepository(Article::class)->findBy(['published' => true], ['created' => 'desc'], 2 - count($pinned));
                $pinned = array_merge($pinned, $others);
            }
            $ids = [];
            foreach ($pinned as $pin) {
                $ids[] = $pin->getId();
            }
        } else {
            $pinned = [];
            $ids = ['-1'];
        }

        $dql = "SELECT a FROM App:Article a where a.published=1 and a.id not in(:ids) order by a.created desc";
        $query = $manager->createQuery($dql);
        $query->setParameter('ids', $ids);

        $pagination = $paginator->paginate(
            $query,
            (int)$page,
            12
        );
        $pagination->setUsedRoute('app_pages');


        return $this->render('default/index.html.twig', [
            'articles' => $pagination,
            'pinned' => $pinned,
        ]);
    }

    #[Route('/articles/{slug}', name: 'app_article')]
    public function article(EntityManagerInterface $manager, SeoPageInterface $seo, CacheManager $cacheManager, Article $article): Response
    {
        $seo->addTitlePrefix($article->getTitle());

        $seo->addMeta('name', 'description', $article->getHeading() ?? $article->getTitle());
        $seo->addMeta('property', "og:title", $article->getTitle());
        $seo->addMeta('property', "og:image", $cacheManager->getBrowserPath('images/' . $article->getImage(), 'thumb'));

        return $this->render('default/article.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/recherche', name: 'app_search')]
    public function search(EntityManagerInterface $manager, Request $request): Response
    {
        $search=$request->query->get('term');

        $articles= [];
        if($search!==null) {
            $dql = "SELECT a FROM App:Article a where a.published=1 and (a.title like :search  or a.body like :search) order by a.created desc ";
            $query = $manager->createQuery($dql);
            $query->setParameter('search', '%' . $search . '%');
            $query->setMaxResults(48);

            $articles = $query->getResult();
        }

        return $this->render('default/search.html.twig', [
            'articles' => $articles,
            'term' => $search,
        ]);
    }

    #[Route('/commander', name: 'app_order')]
    public function order(Request $request, OrderRepository $orderRepository, TransportInterface $mailer): Response
    {
        $order = new Order();
        $order->setEmail($this->getUser()->getEmail());

        $form = $this->createForm(OrderType::class, $order);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $order = $form->getData();

            $orderRepository->save($order,true);

            $email = (new Email())
                ->from('noreply@librairiebasta.ch')
                ->replyTo($order->getEmail())
                ->to($order->getLibrary()->getEmail())
                ->subject('#'.$order->getId().' Commande de '.$order->getName())
                ->html('
<p><strong>Commande de:</strong> '.htmlentities($order->getName()).'</p>
<p><strong>E-mail: </strong>'.$order->getEmail().'</p>
<p><strong>Message:</strong><br>'.nl2br(htmlentities($order->getMessage())).'</p>
');

            $mailer->send($email);

            $this->addFlash('success', 'Votre commande a bien été enregistrée');

            return $this->redirectToRoute('app_order');
        }




        return $this->render('default/order.html.twig', [
            'form' => $form,
        ]);
    }
}
