<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="app_home")
     */
    public function index()
    {
        $this->denyAccessUnlessGranted('ROLE_USER');


        return $this->render('dashboard/index.html.twig', [
            'test' => time(),
        ]);
    }
}