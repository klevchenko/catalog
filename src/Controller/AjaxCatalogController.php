<?php

namespace App\Controller;

use App\Entity\Catalog;
use App\Entity\CatalogItem;
use App\Form\CreateNewCatalog;
use App\Repository\CatalogRepository;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxCatalogController extends AbstractController
{

    /**
     * @Route("/catalogs/ajax-add", name="app_new_ajax_catalog")
     */
    public function new(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $ajaxURL = $this->generateUrl('app_new_ajax_catalog', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $submittedToken = $request->request->get('token');

        $fileName = $request->request->get('fileName');

        if($fileName){

            if(!$this->isCsrfTokenValid('ajax', $submittedToken)){
                return new JsonResponse([
                    'status' => false,
                    'msg'    => 'Помилка. Каталог НЕ створено. Токен невалідний.',
                    'data'   => [],
                ]);
            }

            $catalog = new Catalog();
            $catalog_created = false;

            $catalog->setName($fileName);
            $catalog->setDate(new \DateTime());

            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($catalog);
            $em->flush();

            if($catalog->getId()){
                $JsonResponse = [
                    'status' => true,
                    'msg'    => 'Каталог створено.',
                    'data'   => [
                        'catalog_id' => $catalog->getId()
                    ],
                ];
            } else {
                $JsonResponse = [
                    'status' => false,
                    'msg'    => 'Помилка. Каталог НЕ створено.',
                    'data'   => [],
                ];
            }

            return new JsonResponse($JsonResponse);
        } elseif 
        (
            $request->request->get('catalog_id') 
            && $request->request->get('json')
            && $this->getDoctrine()->getRepository(Catalog::class)->findOneBy(['id' => $request->request->get('catalog_id') ])
        )
        {
            return $this->newCatalogItem($request);
        } else 
        {
            return $this->render('dashboard/catalog/ajax.add.html.twig', [
                'form_title' => 'Новий каталог',
                'ajax_url' => $ajaxURL,
            ]);
        }
    }

    public function newCatalogItem(Request $request)
    {
        
        $catalog_id = $request->request->get('catalog_id');
        $json       = $request->request->get('json');

        $catalog = $this->getDoctrine()->getRepository(Catalog::class)->findOneBy(['id' => $catalog_id]);

        if($catalog && $json && json_decode($json)){

            $data = json_decode($json);

            $number = (isset($data[0]) and !empty($data[0])) ? $data[0] : '';
            $code   = (isset($data[1]) and !empty($data[1])) ? $data[1] : '';
            $price  = (isset($data[2]) and !empty($data[2])) ? $data[2] : '';

            $catalogItem = new CatalogItem();
            $catalogItem->setCatalog($catalog);
            $catalogItem->setCode($code);
            $catalogItem->setNumber($number);
            $catalogItem->setPrice($price);

        
            // Save
            $em = $this->getDoctrine()->getManager();
            $em->persist($catalogItem);
            $em->flush();

            return new JsonResponse([
                'status' => true,
                'msg'    => 'Каталог оновлено.',
                'data'   => [

                ],
            ]);
        }

        return new JsonResponse([
            'status' => false,
            'msg'    => 'Помилка. Каталог НЕ оновлено.',
            'data'   => [],
        ]);
    }

    

}