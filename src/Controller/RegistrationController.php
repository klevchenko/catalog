<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Order;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;




class RegistrationController extends AbstractController
{
    private $passwordEncoder;
    private $mailer;


    public function __construct(UserPasswordEncoderInterface $passwordEncoder, MailerInterface $mailer)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->mailer = $mailer;
    }

    /**
     * @Route("/registration", name="registration")
     */
    public function index(Request $request)
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encode the new users password
            $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));

            // Set their role
            // set ROLE_ADMIN to first registered user
            if($this->getDoctrine()->getRepository(User::class)->findOneBy([])){
                $user->setRoles(['ROLE_USER']);
            } else {
                $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
            }

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

            try {
                $chat = new Chat();
                $chat->setUser($user);
                $chat->setRelOrder(null);

                // Save
                $em = $this->getDoctrine()->getManager();
                $em->persist($chat);
                $em->flush();

                $this->registrationEmail($user->getEmail());
            } catch (\Exception $e) {

            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/registration-test", name="registration_etst")
     */
    public function registrationEmail($userEmail = 'test@test.com') : Response
    {

      $homeURL   = $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
      $domain    = parse_url($homeURL)["host"];
      $loginURL  = $this->generateUrl('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
      $loginURL  = $loginURL . '?email='.$userEmail;

      //TODO: змінити домен

      $email = (new TemplatedEmail())
                 ->from('mailer@your-domain.com')
                 ->to(new Address($userEmail))
                 ->subject('Реєстрація на сайті ' . $domain)
                 ->htmlTemplate('registration/email.html.twig')
                 ->context([
                     'homeURL' => $homeURL,
                     'loginURL' => $loginURL,
                     'user_email' => $userEmail,
                     'domain' => $domain,
                 ])
                 ;

      $this->mailer->send($email);

      return new Response(
        '',
         Response::HTTP_OK
       );

    }

}
