<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationController extends AbstractController
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
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

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}