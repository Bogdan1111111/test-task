<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\Users;
use App\Entity\Programs;
use Doctrine\ORM\EntityManagerInterface;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function index()
    {
        $cookie = isset($_COOKIE['test-task']) ? true : false;
        
        return $this->render('main/index.html.twig', [
            'cookie' => $cookie
        ]);
    }
    
    public function new(Request $request)
    {
        $form = $this->createFormBuilder()
                     ->add('Name', TextType::class)
                     ->add('Create', SubmitType::class)
                     ->getForm();
                     
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            
            $user = new Users();
            $post = $form->getData();
            $user->setName($post['Name']);
            
            $entityManager->persist($user);
            setcookie('test-task', crypt($post['Name'], $user->getId()).';'.$user->getId());
            $entityManager->flush();
            
            return $this->redirectToRoute('index');

        }
          
        return $this->render('main/new.html.twig', [
            'form' => $form->createView()
        ]);
    }
        
    public function create(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository(Users::class);
        $users = $repository->findAll();
        $choices = [];
        
        foreach($users as $user) {
            $choices[] = [$user->getName() => $user->getId()];
        }
        
        $form = $this->createFormBuilder()
                     ->add('Title', TextType::class)
                     ->add('Description', TextareaType::class)
                     ->add('Thumbnail', FileType::class, [
                        'label' => 'Thumbnail (JPEG-file < 1MB)'
                     ])
                     ->add('organizers', ChoiceType::class, [
                        'multiple' => true,
                        'expanded' => true,
                        'attr' => ['class' => 'form-group form-check'],
                        'choice_attr' => function ($a, $b, $c) {
                            return ['class' => 'form-check-input'];
                        },
                        'choices' => $choices,
                        'choice_label' => function ($value, $key, $choiceValue) {
                            return $key;
                        },
                     ])
                     ->add('Create', SubmitType::class)
                     ->getForm();
                     
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            
            $programs = new Programs();
            $post = $form->getData();
            $programs->setTitle($post['Title']);
            $programs->setDescription($post['Description']);
            $programs->setUsers(serialize($post['organizers']);
            $programs->setLevel(0);
            $programs->setParentId(0);
            
            $entityManager->persist($programs);
            $entityManager->flush();
            
            return $this->redirectToRoute('index');

        }
        
        return $this->render('main/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
