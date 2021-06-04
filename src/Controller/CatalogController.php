<?php

namespace App\Controller;

use App\Entity\Catalog;
use App\Entity\CatalogItem;
use App\Form\CreateNewCatalog;
use App\Repository\CatalogRepository;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;

class CatalogController extends AbstractController
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
     * @Route("/catalogs", name="app_catalogs")
     */
    public function index(CatalogRepository $catalogRepository)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('admin/catalog/index.html.twig', [
            'catalogs' => $catalogRepository->getAll()
        ]);
    }

    /**
     * @Route("/catalogs/new", name="app_new_catalog")
     */
    public function new(Request $request, FileUploader $file_uploader)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $catalog = new Catalog();
        $catalog_created = false;

        $form = $this->createForm(CreateNewCatalog::class, $catalog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {


            $file = $form['upload_file']->getData();
            if ($file)
            {
                $file_name = $file_uploader->upload($file);
                if (null !== $file_name) // for example
                {

                    $catalog->setName($file_name);
                    $catalog->setDate(new \DateTime());

                    // Save
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($catalog);
                    $em->flush();

                    $directory = $file_uploader->getTargetDirectory();
                    $full_path = $directory.'/'.$file_name;

                    if (($handle = fopen($full_path, "r")) !== FALSE) {
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

                            $number = (isset($data[0]) and !empty($data[0])) ? $data[0] : '';
                            $code   = (isset($data[1]) and !empty($data[1])) ? $data[1] : '';
                            $price  = (isset($data[2]) and !empty($data[2])) ? $data[2] : '';

                            $price = floatval($price);

                            if( (!empty($price) || !empty($code)) && $price > 0 ){
                                $catalogItem = new CatalogItem();
                                $catalogItem->setCatalog($catalog);
                                $catalogItem->setCode($code);
                                $catalogItem->setNumber($number);
                                $catalogItem->setPrice($price);

                                // Save
                                $em = $this->getDoctrine()->getManager();
                                $em->persist($catalogItem);

                                if($catalog_created === false and $catalog and $catalogItem){
                                    $catalog_created = true;
                                }
                            }
                        }
                        fclose($handle);
                        $em->flush();
                    }
                }
            }

            if($catalog_created){
                $this->addFlash('success','Каталог додано.');
                return $this->redirectToRoute('app_catalogs');
            } else {
                $this->addFlash('error','Помилка при додаванні каталога.');
                return $this->redirectToRoute('app_catalogs');
            }
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error','Помилка при додаванні каталога.');
            return $this->render('admin/catalog/add.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Новий каталог',
                'submit_btn_text' => 'Додати',
            ]);
        } else {
            return $this->render('admin/catalog/add.html.twig', [
                'form' => $form->createView(),
                'form_title' => 'Новий каталог',
                'submit_btn_text' => 'Додати',
            ]);
        }
    }

    /**
     * @Route("/catalog/view/{id}", name="app_view_catalog")
     */
    public function view(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        //TODO: Уточнити як формується цінв
        //TODO: Поправити формування ціни при перегляді

        $catalog = $this->getDoctrine()->getRepository(Catalog::class)->find($id);
        $catalog_items = $this->getDoctrine()->getRepository(CatalogItem::class)->findBy(['catalog' => $catalog->getId()]);

        return $this->render('admin/catalog/single.html.twig', [
            'catalogname' => $catalog->getName(),
            'catalogitems' => $catalog_items,
        ]);
    }

    /**
     * @Route("/catalog/download/{id}", name="app_download_catalog")
     */
    public function download(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->security->getUser();
        $userGroup = $user->getUGroup();
        $extraCharge = floatval($userGroup->getExtraCharge()) ?? 1;

        $catalog = $this->getDoctrine()->getRepository(Catalog::class)->find($id);
        $catalog_items = $this->getDoctrine()->getRepository(CatalogItem::class)->findBy(['catalog' => $catalog->getId()]);

        $csvStr = '';

        foreach ($catalog_items as $catalog_item){

            //TODO: Уточнити як формується цінв
            $price = $catalog_item->getPrice() * $extraCharge;

            $csvStr .= '"'.$catalog_item->getNumber().'","'.$catalog_item->getCode().'","'.$price.'"' . "\n";

        }

        $response = new Response($csvStr);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-disposition','attachment; filename="'.$catalog->getName().'"');

        return $response;
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

        $this->addFlash('success','Каталог видалено!');
        return $this->redirectToRoute('app_catalogs');
    }


}