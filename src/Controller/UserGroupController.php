<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Form\CreateUserGroup;
use App\Form\SetDefaultUserGroup;
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
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/user-group/index.html.twig', [
            'usergroups' => $userGroupRepository->getAll(),
        ]);
    }

    /**
     * @Route("/user-groups/new", name="app_add_user_group")
     */
    public function new(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user_g = new UserGroup();

        $form = $this->createForm(CreateUserGroup::class, $user_g);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if($user_g->getDefaultGroup() == true){
                $this->setAllDefToFalse();
                $user_g->setDefaultGroup(true);
            }

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($user_g);
            $em->flush();

            $this->addFlash('success', 'Групу "'.$user_g->getName().'" створено.');
            return $this->redirectToRoute('app_user_groups');
        } else {
            return $this->render('admin/user-group/add.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Нова група',
                'submit_btn_text' => 'Додати',
            ]);
        }
    }

    /**
     * @Route("/user-groups/set-default", name="app_set_default_user_group")
     */
    public function setDefault(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $def_group_raw = $request->request->get('set_default_user_group');
        $def_group_id = ( isset($def_group_raw['default_group']) && !empty($def_group_raw['default_group']) ) ? $def_group_raw['default_group'] : false;

        if($def_group_id){
            $user_g = $this->getDoctrine()->getRepository(UserGroup::class)->findOneBy(['id' => $def_group_id]);

            if($user_g){
                $this->setAllDefToFalse();
            }

            $user_g->setDefaultGroup(true);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user_g);
            $em->flush();

            $this->addFlash('success', 'Групу за замовчуванням змінено на "'.$user_g->getName().'"');
            return $this->redirectToRoute('app_user_groups');
        } else {
            $user_g = new UserGroup();
        }

        $form = $this->createForm(SetDefaultUserGroup::class, $user_g);
        $form->handleRequest($request);

        return $this->render('admin/user-group/set.default.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Встановлення групи за замовчуванням',
            'submit_btn_text' => 'Встановити',
        ]);
    }

    /**
     * @Route("/user-groups/edit/{id}", name="app_edit_user_group")
     */
    public function edit(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user_g = $this->getDoctrine()->getRepository(UserGroup::class)->find($id);

        $form = $this->createForm(CreateUserGroup::class, $user_g);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if($user_g->getDefaultGroup() == true){
                $this->setAllDefToFalse();
                $user_g->setDefaultGroup(true);
            }

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($user_g);
            $em->flush();

            $this->addFlash('success', 'Групу "'.$user_g->getName().'" змінено.');
            return $this->redirectToRoute('app_user_groups');
        } else {
            return $this->render('admin/user-group/add.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Змінити групу ' . $user_g->getName(),
                'submit_btn_text' => 'Змінити',
            ]);
        }
    }

    /**
     * @Route("/user-groups/delete/{id}", name="app_delete_user_group")
     */
    public function delete(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager = $this->getDoctrine()->getManager();
        $user_g = $this->getDoctrine()->getRepository(UserGroup::class)->find($id);

        if($this->getDoctrine()->getRepository(User::class)->findOneBy(['u_group' => $user_g])){
            $this->addFlash('error', 'Групу "'.$user_g->getName().'" пов`язано з корустувачем(ами) тому її неможливо видалити!');
            return $this->redirectToRoute('app_user_groups');
        }

        if(count($this->getDoctrine()->getRepository(UserGroup::class)->findAll()) === 1 ){
            $this->addFlash('error', 'Не можна видаляти останню групу!');
            return $this->redirectToRoute('app_user_groups');
        }

        if($user_g->getDefaultGroup() == true){
            $this->addFlash('error', 'Не можна видаляти групу за замовчуванням!');
            return $this->redirectToRoute('app_user_groups');
        }

        $entityManager->remove($user_g);
        $entityManager->flush();

        $this->addFlash('success', 'Групу "'.$user_g->getName().'" видалено.');
        return $this->redirectToRoute('app_user_groups');
    }

    protected function setAllDefToFalse(){
        $allUserGroups = $this->getDoctrine()->getRepository(UserGroup::class)->findAll();

        foreach ($allUserGroups as $allUserGroup){

            $allUserGroup->setDefaultGroup(false);

            $em = $this->getDoctrine()->getManager();
            $em->persist($allUserGroup);
            $em->flush();
        }
    }

}