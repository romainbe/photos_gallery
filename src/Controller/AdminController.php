<?php

/**
 * This file is part of the photos_gallery project
 * 
 * Author: Romain Bertholon <romain.bertholon@gmail.com>
 */

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\User;
use App\Form\TagType;
use App\Form\RegistrationType;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * This class is used to create the admin user, and to access the admin page where the admin can
 * create tags for the pictures.
 */
class AdminController extends AbstractController
{
    /**
     * Register the admin user, if he does not already exists, when the form is submitted.
     * 
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordEncoderInterface $encoder
     * @param  UserRepository $userRepo
     * 
     * @Route("/admin/registration", name="admin_register")
     */
    public function registration(Request $request, EntityManagerInterface $manager,
        UserPasswordEncoderInterface $encoder, UserRepository $userRepo): Response
    {
        // search for an admin user ; there's probably a better way to do this.
        $users = $userRepo->findAll();
        foreach ($users as $user) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                $this->addFlash('error', 'Admin user already exists.');
                return $this->redirectToRoute('security_login');
            }
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hash = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($hash)
                 ->addAdminRole();
            $manager->persist($user);
            $manager->flush();

            $this->addFlash('success', 'Admin');
    
            return $this->redirectToRoute('security_login');
        }

        return $this->render('admin/registration.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Main admin page, accessible only to the admin. Used to create tags.
     * 
     * @param Request $request
     * @param EntityManagerInterface $manager,
     * @param TagRepository $tagRepo
     * 
     * @Route("/admin", name="admin_home")
     */
    public function index(Request $request, EntityManagerInterface $manager,
        TagRepository $tagRepo) : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $tags = $tagRepo->findAll();
        $tag = new Tag();
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);   
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($tag);
            $manager->flush();
            $tags[] = $tag;
            
            $this->addFlash('success', $tag->getName());

            unset($tag);
            unset($form);
            $tag = new Tag();
            $form = $this->createForm(TagType::class, $tag);
        }

        return $this->render('admin/index.html.twig', [
            'tags' => $tags,
            'formTag' => $form->createView()
        ]);
    }
}
