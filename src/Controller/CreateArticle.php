<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use App\Entity\Article;
use App\Entity\Tag;
use App\Events;
use App\Form\Type\ArticleType;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class CreateArticle extends Controller
{
    /**
     * @Route("/blogposts", methods={"GET"}, name="create_form"))
     */
    public function __invoke(Request $request, EntityManagerInterface $em, AuthenticationUtils $authenticationUtils)
    {
        $form = $this->createForm(ArticleType::class);
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        if (!$lastUsername || $lastUsername == null || $lastUsername == "") {
            return $this->redirect( $this->generateUrl('login', ['last_username' => $lastUsername, 'error' => $error]) );
        }
        return $this->render('createForm.html.twig',[
            'form' => $form->createView(),
        ]);
    }

     /**
     * @Route("/blogposts", methods={"POST"}, name="create_post"))
     */
    public function createPost(Request $request, EntityManagerInterface $em, EventDispatcherInterface $eventDispatcher): Response
    {

        $comment = new Article();
        $is_Connexion = $request->get('is_connexion');
        var_dump($is_Connexion);
        if ($is_Connexion == true) {
            $form = $this->createForm(ArticleType::class);
            return $this->render('createForm.html.twig',[
                'form' => $form->createView(),
            ]);
        }

        $form = $this->createForm(ArticleType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $tags = explode(" ",$comment->getTags());
            $array = array();
            $comment->setTag($array);
            for($i=0;$i<count($tags);$i++){
                // VERIFIER QUE LE TAGS N EXISTE PAS 
                $tag = new Tag();
                $tag->setName($tags[$i]);
                $tag->setArticle($comment->getTitle());
                $em->persist($tag);
                $comment->addTag($tag);
            }
            $em->persist($comment);
            $em->flush();

            $event = new GenericEvent($comment);
            $eventDispatcher->dispatch(Events::ARTICLE_CREATED, $event);

            return $this->redirectToRoute('home');
        }

        return $this->render('createFormError.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}