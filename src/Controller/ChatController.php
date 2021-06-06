<?php

namespace App\Controller;

use App\Entity\Catalog;
use App\Entity\Chat;
use App\Entity\Message;
use App\Repository\ChatRepository;
use App\Repository\MessageRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class ChatController extends AbstractController
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
     * @Route("/chat/{id}/get/", name="app_get_chat")
     */
    public function getChat(Request $request, MessageRepository $messageRepository, $id)
    {
        $submittedToken = $request->query->get('token');
        $def_chat_id    = $request->query->get('def_chat_id');

        if ($def_chat_id !== $id || !$this->isCsrfTokenValid('chat', $submittedToken)) {
            return new JsonResponse([
                'status' => false,
                'msg' => 'Помилка. Токен невалідний.',
                'data' => [],
            ]);
        }

        $chat = $this->getDoctrine()->getRepository(Chat::class)->findOneBy(array('id' => $id));

        if(!$chat){
            return new JsonResponse([
                'status' => false,
                'msg' => 'Помилка. Чату не існує.',
                'data' => [],
            ]);
        }

        $messages = $messageRepository->findAll(['chat' => $id]);

        $JsonResponse = [
            'status' => true,
            'messages' => [],

        ];

        if($messages){
            foreach ($messages as $message){
                $JsonResponse['messages'][] = [
                    'id' => $message->getId(),
                    'text' => $message->getText(),
                    'date' => $message->getDate()->format('d-m-Y'),
                    'user' => $message->getUser()->getId(),
                ];
            }
        }

        return new JsonResponse($JsonResponse);
    }

    /**
     * @Route("/chat/{id}/mew-msg/", name="app_chat_new_msg")
     */
    public function newMsg(Request $request, MessageRepository $messageRepository, $id)
    {
        $submittedToken = $request->request->get('token');
        $message        = $request->request->get('message');
        $curUser = $this->security->getUser();

        if (!$this->isCsrfTokenValid('chat', $submittedToken)) {
            return new JsonResponse([
                'status' => false,
                'msg' => 'Помилка. Токен невалідний.',
                'data' => [],
            ]);
        }

        $chat = $this->getDoctrine()->getRepository(Chat::class)->findOneBy(array('id' => $id));

        if(!$chat){
            return new JsonResponse([
                'status' => false,
                'msg' => 'Помилка. Чату не існує.',
                'data' => [],
            ]);
        }

        $msg = new Message();
        $msg->setChat($chat);
        $msg->setUser($curUser);
        $msg->setDate(new \DateTime());
        $msg->setIsUnread(true);
        $msg->setText($message);

        // Save
        $em = $this->getDoctrine()->getManager();
        $em->persist($msg);
        $em->flush();

        if ($msg->getId()) {
            $JsonResponse = [
                'status' => true,
            ];
        } else {
            $JsonResponse = [
                'status' => false,

            ];
        }

        return new JsonResponse($JsonResponse);
    }

}