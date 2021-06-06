<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\Order;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Form\CreateNewUser;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;

class UserController extends AbstractController
{
    /**
     * @var Security
     */
    private $security;
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, Security $security)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->security = $security;
    }

    /**
     * @Route("/users", name="app_users")
     */
    public function index(UserRepository $userRepository)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->security->getUser();

        if(in_array('ROLE_ADMIN', $user->getRoles())){
            $users = $this->getDoctrine()->getRepository(User::class)->findBy(array(), array('name' => 'ASC'));
        } elseif (in_array('ROLE_USER', $user->getRoles()) &&  $user->getId()) {
            $users = $this->getDoctrine()->getRepository(User::class)->findBy(array('id' => $user->getId()), array('name' => 'ASC'));
        }

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'is_user_admin' => in_array('ROLE_ADMIN', $user->getRoles()),
            'this_user_id' => $user->getId()
        ]);
    }

    /**
     * @Route("/users/new", name="app_new_users")
     */
    public function new(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $curUser = $this->security->getUser();
        $user = new User();

        $form = $this->createForm(CreateNewUser::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //TODO: Додати перевірку на існування емейлу

            // Encode the new users password
            $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));

            // Set their role
            $user->setRoles(['ROLE_USER']);

            $userGroup = $this->getDoctrine()->getRepository(UserGroup::class)->findOneBy(['default_group' => true]);

            if(!$userGroup){
                $userGroup = $this->getDoctrine()->getRepository(UserGroup::class)->findOneBy([]);
            }

            if(!$userGroup){
                $userGroup = new UserGroup();
                $userGroup->setName('Default');
                $userGroup->setExtraCharge(10);

                // Save
                $em = $this->getDoctrine()->getManager();
                $em->persist($userGroup);
                $em->flush();
            }

            $user->setUGroup($userGroup);

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->sendAddEmail($user);

            $this->addFlash('success', 'Користувача додано.');
            return $this->redirectToRoute('app_users');
        } else {
            return $this->render('admin/user/add.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Новий користувач',
                'submit_btn_text' => 'Додати',
                'is_user_admin' => in_array('ROLE_ADMIN', $curUser->getRoles())
            ]);
        }
    }

    /**
     * @Route("/users/edit/{id}", name="app_edit_users")
     */
    public function edit(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $curUser = $this->security->getUser();
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        if($this->isCanEdit($curUser, $user) === false){
            $this->addFlash('error', 'Ви можете змінювати тільки свої данні!');
            return $this->redirectToRoute('app_users');
        }

        $form = $this->createForm(CreateNewUser::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //TODO: Додати перевірку на існування емейлу

            // Encode the new users password
            $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));

            $userGroup = $this->getDoctrine()->getRepository(UserGroup::class)->findOneBy(['default_group' => true]);

            if(!$userGroup){
                $userGroup = $this->getDoctrine()->getRepository(UserGroup::class)->findOneBy([]);
            }

            if(!$userGroup){
                $userGroup = new UserGroup();
                $userGroup->setName('Default');
                $userGroup->setExtraCharge(10);

                // Save
                $em = $this->getDoctrine()->getManager();
                $em->persist($userGroup);
                $em->flush();
            }

            $user->setUGroup($userGroup);

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->sendEditEmail($user);

            if($curUser->getId() === $user->getId()){
                $this->addFlash('success', 'Дані оновлено.');
                return $this->redirectToRoute('app_user_profile');
            }
            elseif (in_array('ROLE_ADMIN', $curUser->getRoles())){
                $this->addFlash('success', 'Користувача змінено.');
                return $this->redirectToRoute('app_users');
            }
            else {
                $this->addFlash('success', 'Дані змінено.');
                return $this->redirectToRoute('app_user_profile');
            }

        } else {
            return $this->render('admin/user/add.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Редагування користувача ' . $user->getEmail(),
                'submit_btn_text' => 'Змінити',
                'is_user_admin' => in_array('ROLE_ADMIN', $curUser->getRoles())
            ]);
        }
    }

    /**
     * @Route("/user/view/{id}", name="app_view_user_profile")
     */
    public function viewProfile($id)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->getDoctrine()->getRepository(User::class)->find($id);
        $orders = $this->getDoctrine()->getRepository(Order::class)->findBy(array('user' => $user->getId()), array('date' => 'DESC'), 10);
        $def_chat = $this->getDoctrine()->getRepository(Chat::class)->findOneBy(array('user' => $user->getId(), 'rel_order' => null));
        $def_chatID = $def_chat && $def_chat->getId() ? $def_chat->getId() : false;

        $ajaxGetChat = $this->generateUrl('app_get_chat', ['id' => $def_chatID], UrlGeneratorInterface::ABSOLUTE_URL);
        $ajaxChatNewMsg = $this->generateUrl('app_chat_new_msg', ['id' => $def_chatID], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->render('admin/user/single.html.twig', [
            'user' => $user,
            'orders' => $orders,
            'is_user_admin' => in_array('ROLE_ADMIN', $user->getRoles()),
            'def_chat_id' => $def_chatID,
            'ajax_get_chat_url' => $ajaxGetChat,
            'app_chat_new_msg' => $ajaxChatNewMsg,
        ]);
    }

    /**
     * @Route("/user/profile", name="app_user_profile")
     */
    public function profile()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $curUser = $this->security->getUser();
        $orders = $this->getDoctrine()->getRepository(Order::class)->findBy(array('user' => $curUser->getId()), array('date' => 'DESC'), 5);
        $def_chat = $this->getDoctrine()->getRepository(Chat::class)->findOneBy(array('user' => $curUser->getId(), 'rel_order' => null));
        $def_chatID = $def_chat && $def_chat->getId() ? $def_chat->getId() : false;

        $ajaxGetChat = $this->generateUrl('app_get_chat', ['id' => $def_chatID], UrlGeneratorInterface::ABSOLUTE_URL);
        $ajaxChatNewMsg = $this->generateUrl('app_chat_new_msg', ['id' => $def_chatID], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->render('admin/user/single.html.twig', [
            'user' => $curUser,
            'orders' => $orders,
            'is_user_admin' => in_array('ROLE_ADMIN', $curUser->getRoles()),
            'def_chat_id' => $def_chatID,
            'ajax_get_chat_url' => $ajaxGetChat,
            'app_chat_new_msg' => $ajaxChatNewMsg,
        ]);
    }

    /**
     * @Route("/users/delete/{id}", name="app_delete_user")
     */
    public function delete($id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        //TODO: Додати перевірки на зв'язані каталоги або замовлення

        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository(User::class)->find($id);

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Користувача "'.$user->getEmail().'" видалено.');
        return $this->redirectToRoute('app_users');
    }

    private function isCanEdit(User $curUser, User $user){

        if(in_array('ROLE_ADMIN', $curUser->getRoles())){
            return true;
        } elseif ($user->getId() === $curUser->getId()){
            return true;
        }

        return false;
    }

    private function sendAddEmail(User $user){
        //TODO: Додати відправку мейла при додаванні юзера
    }

    private function sendEditEmail(User $user){
        //TODO: Додати відправку мейла при зміні юзера
    }
}