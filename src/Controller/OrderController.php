<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Order;
use App\Entity\User;
use App\Form\CreateNewOrder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;

class OrderController extends AbstractController
{

    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @Route("/orders", name="app_orders")
     */
    public function index()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->security->getUser();

        if(in_array('ROLE_ADMIN', $user->getRoles())){
            $orders = $this->getDoctrine()->getRepository(Order::class)->findBy(array(), array('date' => 'DESC'));
        } elseif (in_array('ROLE_USER', $user->getRoles()) &&  $user->getId()) {
            $orders = $this->getDoctrine()->getRepository(Order::class)->findBy(array('user' => $user->getId()), array('date' => 'DESC'));
        }

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
            'is_user_admin' => in_array('ROLE_ADMIN', $user->getRoles()),
        ]);
    }

    /**
     * @Route("/orders/new", name="app_new_order")
     */
    public function new(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $userID = $request->query->get('user');

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => $userID]);

        if(!$user){
            $user = $this->security->getUser();
        }

        $order = new Order();

        $form = $this->createForm(CreateNewOrder::class, $order);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $order->setDate(new \DateTime());
            $order->setUser($user);

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($order);
            $em->flush();

            if($this->getDoctrine()->getRepository(Order::class)->findBy(array('id' => $order->getId()))){

                $chat = new Chat();
                $chat->setUser($user);
                $chat->setRelOrder($order);

                // Save
                $em = $this->getDoctrine()->getManager();
                $em->persist($chat);
                $em->flush();
            }

            return $this->redirectToRoute('app_orders');
        } else {
            return $this->render('admin/order/add.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Новe замовлення',
                'submit_btn_text' => 'Замовити',
            ]);
        }
    }

    /**
     * @Route("/order/edit/{id}", name="app_edit_order")
     */
    public function edit(Request $request, $id)
    {
        $cur_user = $this->security->getUser();
        $order = $this->getDoctrine()->getRepository(Order::class)->find($id);

        $form = $this->createForm(CreateNewOrder::class, $order);
        $form->handleRequest($request);

        if(!$cur_user){
            $this->addFlash('error', 'Помилка. Перевірте посилання або спробуйте пізніше.');
            return $this->redirectToRoute('app_orders');
        }

        if(!$order){
            $this->addFlash('error', 'Помилка. Перевірте посилання або спробуйте пізніше.');
            return $this->redirectToRoute('app_orders');
        }

        if($this->canEdit($cur_user, $order) === false){
            $this->addFlash('error', 'Редагувати замовлення може тільки адміністратор!');
            return $this->redirectToRoute('app_orders');
        }

        if ( $form->isSubmitted() && $form->isValid() ) {

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($order);
            $em->flush();

            $this->addFlash('success', 'Замовлення оновлено.');
            return $this->redirectToRoute('app_orders');

        }

        return $this->render('admin/order/add.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Редагування замовлення',
            'submit_btn_text' => 'Змінити',
        ]);
    }

    /**
     * @Route("/order/view/{id}", name="app_view_order")
     */
    public function view($id)
    {
        $cur_user = $this->security->getUser();
        $entityManager = $this->getDoctrine()->getManager();
        $order = $this->getDoctrine()->getRepository(Order::class)->find($id);

        if(!$order){
            $this->addFlash('error', 'Помилка. Перевірте посилання або спробуйте пізніше.');
            return $this->redirectToRoute('app_orders');
        }

        $orderUser = $order->getUser();

        if($this->canEdit($cur_user) || $cur_user->getID() === $orderUser->getId()){
            return $this->render('admin/order/single.html.twig', [
                'order' => $order,
                'form_title' => 'Замовлення №' . $order->getId(),
            ]);
        }

        $this->addFlash('error', 'Ви можете переглядати тільки свої замовлення!');
        return $this->redirectToRoute('app_orders');
    }

    /**
     * @Route("/order/delete/{id}", name="app_delete_order")
     */
    public function delete(Request $request, $id)
    {
        $this->addFlash('error', 'Видаляти замовлення може тільки адміністратор!');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager = $this->getDoctrine()->getManager();
        $order = $this->getDoctrine()->getRepository(Order::class)->find($id);

        $entityManager->remove($order);
        $entityManager->flush();

        return $this->redirectToRoute('app_orders');
    }

    private function canEdit(User $user){

        if(in_array('ROLE_ADMIN', $user->getRoles())){
            return true;
        }

        return false;
    }

}