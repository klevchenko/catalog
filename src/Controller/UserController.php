<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Form\CreateNewUser;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserController extends AbstractController
{

    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/users", name="app_users")
     */
    public function index(UserRepository $userRepository)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('dashboard/user/index.html.twig', [
            'users' => $userRepository->getAll()
        ]);
    }

    /**
     * @Route("/users/new", name="app_new_users")
     */
    public function new(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = new User();

        $form = $this->createForm(CreateNewUser::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Encode the new users password
            $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));

            // Set their role
            $user->setRoles(['ROLE_USER']);

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('app_users');
        } else {
            return $this->render('dashboard/user/add.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Новий користувач',
                'submit_btn_text' => 'Додати',
            ]);
        }
    }

    /**
     * @Route("/users/edit/{id}", name="app_edit_users")
     */
    public function edit(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        $form = $this->createForm(CreateNewUser::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Encode the new users password
            $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));

            // Set their role
            $user->setRoles(['ROLE_USER']);

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('app_users');
        } else {
            return $this->render('dashboard/user/add.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Редагування користувача ' . $user->getEmail(),
                'submit_btn_text' => 'Змінити',
            ]);
        }
    }

    /**
     * @Route("/users/delete/{id}", name="app_delete_user")
     */
    public function delete(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_users');
    }
}