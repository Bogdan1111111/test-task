<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Users;
use App\Entity\Programs;

class MainController extends AbstractController
{
    
    /**
     * @Route("/", name="main")
     */
    public function index()
    {
        $cookie = isset($_COOKIE['test-task']) ? true : false;
        
        $repository = $this->getDoctrine()->getRepository(Programs::class);
        $programs = $repository->findAll();
        
        $id = isset($_COOKIE['test-task']) ? $_COOKIE['test-task'] : 0;
        
        foreach ($programs as $key=>$value) {
            $array = unserialize($programs[$key]->getUsers());
            if (in_array($id, $array)) {
                $programs[$key]->link = 'edit';
            } else {
                $programs[$key]->link = 'view';
            }
        }
        
        return $this->render('main/index.html.twig', [
            'cookie' => $cookie,
            'programs' => $programs
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
            $entityManager->flush();
            setcookie('test-task', $user->getId());
            
            return $this->redirectToRoute('index');

        }
          
        return $this->render('main/new.html.twig', [
            'form' => $form->createView()
        ]);
    }
        
    public function create(Request $request, $slug = false, $parent = 0)
    {
        $repository = $this->getDoctrine()->getRepository(Users::class);
        $users = $repository->findAll();
        
        $progRepository = $this->getDoctrine()->getRepository(Programs::class);
        
        if ($slug) {
            $prograM = $progRepository->find($slug);
        }
        
        if ($parent) {
            $program = $progRepository->find($parent);
        }
        
        $choices = [];
        $creators = [];
        
        foreach($users as $user) {
            if($_COOKIE['test-task'] === $user->getId()) continue;
            if ($slug) {
                if (in_array($user->getId(), unserialize($prograM->getUsers()))) {
                    $creators[] = [
                        'id' => $user->getId(),
                        'name' => $user->getName()
                    ];
                    continue;
                }
            }
            if ($parent) {
                if (in_array($user->getId(), unserialize($program->getUsers()))) continue;
            }
            $choices[] = [$user->getName() => $user->getId()];
        }
        
        $level = $parent ? $program->getLevel() + 1 : 0;
        
        if (!$level) $level = ($slug) ? $prograM->getLevel() : 0;
        
        $title = $slug ? $prograM->getTitle() : null;
        $parent = $slug ? $prograM->getParentId() : $parent;
        
        $form = $this->createFormBuilder()
                     ->add('Title', TextType::class, [
                        'attr' => ['value' => $slug ? $prograM->getTitle() : null]
                     ])
                     ->add('Description', TextareaType::class, [
                        'data' => $slug ? $prograM->getDescription() : null
                     ])
                     ->add('Thumbnail', FileType::class, [
                        'label' => 'Thumbnail (JPEG-file < 1MB)'
                     ])
                     ->add('organizers', ChoiceType::class, [
                        'multiple' => true,
                        'expanded' => true,
                        'choices' => $choices,
                        'choice_label' => function ($value, $key, $choiceValue) {
                            return $key;
                        },
                     ])
                     ->add('parent', HiddenType::class, [
                        'data' => $parent
                     ])
                     ->add('level', HiddenType::class, [
                        'data' => $level
                     ])
                     ->add('Create', SubmitType::class)
                     ->getForm();
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            
            if ($slug) {
                $programs = $prograM;
            } else {
                $programs = new Programs();
            }
            
            $post = $form->getData();
            $programs->setTitle($post['Title']);
            $programs->setDescription($post['Description']);
            
            $file = $form['Thumbnail']->getData();
            $fileName = $this->generateUniqueFileName().'.'.$file->guessExtension();
            $programs->setImages($fileName);
            $file->move('images', $fileName);
            
            $response = $request->request->all();
            
            $organizers1 = $response['creators'];
            $organizers2 = $post['organizers'];
            $organizers = array_merge($organizers1, $organizers2);
            $organizers[] = $_COOKIE['test-task'];
            
            $programs->setUsers(serialize($organizers));
            $programs->setLevel($level);
            $programs->setParentId($post['parent']);
            
            $entityManager->persist($programs);
            $entityManager->flush();
            
            return $this->redirectToRoute('index');
        }
        
        return $this->render('main/create.html.twig', [
            'form' => $form->createView(),
            'title' => $title,
            'slug' => isset($slug) ? $slug : null,
            'parent' => isset($parent) ? $parent : 0,
            'id' => (isset($slug) && isset($prograM)) ? $prograM->getId() : null,
            'createChild' => ($level < 2 && $title) ? true : false,
            'creators' => $creators,
            'cookie' => $_COOKIE['test-task']
        ]);
    }
    
    public function view($slug)
    {
        $repository = $this->getDoctrine()->getRepository(Programs::class);
        $program = $repository->find($slug);
        
        $organizers = unserialize($program->getUsers());
        
        $names = [];
        $repository = $this->getDoctrine()->getRepository(Users::class);
        
        foreach ($organizers as $organizer) {
            $names[] = $repository->find($organizer)->getName();
        }
        
        return $this->render('main/view.html.twig', [
            'program' => $program,
            'organizers' => $names
        ]);
    }
    
    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }
}
