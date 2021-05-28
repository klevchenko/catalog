<?php

namespace App\Controller;

use App\Entity\UserGroup;
use App\Form\CreateUserGroup;
use App\Repository\UserGroupRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserGroupController extends AbstractController
{

    /**
     * @Route("/user-groups", name="app_user_groups")
     */
    public function index(UserGroupRepository $userGroupRepository)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('dashboard/user-group/index.html.twig', [
            'usergroups' => $userGroupRepository->getAll()
        ]);
    }

    /**
     * @Route("/user-groups/new", name="app_add_user_group")
     */
    public function new(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user_g = new UserGroup();

        $form = $this->createForm(CreateUserGroup::class, $user_g);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($user_g);
            $em->flush();

            return $this->redirectToRoute('app_user_groups');
        } else {
            return $this->render('dashboard/user-group/add.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Add new user group',
                'submit_btn_text' => 'Add new user group',
            ]);
        }
    }

    /**
     * @Route("/user-groups/edit/{id}", name="app_edit_user_group")
     */
    public function edit(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user_g = $this->getDoctrine()->getRepository(UserGroup::class)->find($id);

        $form = $this->createForm(CreateUserGroup::class, $user_g);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($user_g);
            $em->flush();

            return $this->redirectToRoute('app_user_groups');
        } else {
            return $this->render('dashboard/user-group/add.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Add new user group',
                'submit_btn_text' => 'Add new user group',
            ]);
        }
    }

    /**
     * @Route("/user-groups/delete/{id}", name="app_delete_user_group")
     */
    public function delete(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $entityManager = $this->getDoctrine()->getManager();
        $user_g = $this->getDoctrine()->getRepository(UserGroup::class)->find($id);

        $entityManager->remove($user_g);
        $entityManager->flush();

        return $this->redirectToRoute('app_user_groups');
    }

}