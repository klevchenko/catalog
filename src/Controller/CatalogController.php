<?php

namespace App\Controller;

use App\Entity\Catalog;
use App\Form\CreateNewCatalog;
use App\Repository\CatalogRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CatalogController extends AbstractController
{
    /**
     * @Route("/catalogs", name="app_catalogs")
     */
    public function index(CatalogRepository $catalogRepository)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('dashboard/catalog/index.html.twig', [
            'catalogs' => $catalogRepository->getAll()
        ]);
    }

    /**
     * @Route("/catalogs/new", name="app_new_catalog")
     */
    public function new(Request $request)
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $catalog = new Catalog();

        $form = $this->createForm(CreateNewCatalog::class, $catalog);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $catalog->setDate(new \DateTime());

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($catalog);
            $em->flush();

            return $this->redirectToRoute('app_catalogs');
        } else {
            return $this->render('dashboard/catalog/add.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Новий каталог',
                'submit_btn_text' => 'Додати',
            ]);
        }
    }

    /**
     * @Route("/catalog/delete/{id}", name="app_delete_catalog")
     */
    public function delete(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository(Catalog::class)->find($id);

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_catalogs');
    }


}