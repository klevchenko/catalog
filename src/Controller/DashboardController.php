<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;

class DashboardController extends AbstractController
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
     * @Route("/", name="app_home")
     */
    public function index()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $curUser = $this->security->getUser();

        if(!in_array('ROLE_ADMIN', $curUser->getRoles())){
            return $this->redirectToRoute('app_user_profile');
        }
        
        return $this->render('admin/index.html.twig', [
            'test' => time(),
        ]);
    }
}