<?php

namespace App\Controller;

use App\Entity\Catalog;
use App\Entity\CatalogItem;
use App\Form\CreateNewCatalog;
use App\Repository\CatalogItemRepository;
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

        $user = $this->security->getUser();

        return $this->render('admin/catalog/index.html.twig', [
            'catalogs' => $catalogRepository->getAll(),
            'is_user_admin' => in_array('ROLE_ADMIN', $user->getRoles()),
        ]);
    }

    /**
     * @Route("/catalogs/new", name="app_new_catalog")
     */
    public function new(Request $request, FileUploader $file_uploader, CatalogItemRepository $catalogItemRepository)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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
                    $full_import_file_path = $directory.'/'.'tmp.csv';

                    // create tnp file
                    file_put_contents($full_import_file_path, '');

                    // prepare tmp file to LOAD DATA LOCAL INFILE
                    if (($handle = fopen($full_path, "r")) !== FALSE) {
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

                            $number = (isset($data[0]) and !empty($data[0])) ? $data[0] : '';
                            $code = (isset($data[1]) and !empty($data[1])) ? $data[1] : '';
                            $price = (isset($data[2]) and !empty($data[2])) ? $data[2] : '';

                            $price = floatval($price);

                            if (!empty($number) || !empty($code)) {
                                file_put_contents($full_import_file_path, $catalog->getId() . ',' . $number . ',' . $code . ',' . $price . "\n", FILE_APPEND);
                            }
                        }
                        fclose($handle);
                    }

                    // LOAD DATA LOCAL INFILE
                    try
                    {
                        $dbname = $this->getDoctrine()->getConnection()->getDatabase();
                        $dbhost = $this->getDoctrine()->getConnection()->getHost();
                        $dbuser = $this->getDoctrine()->getConnection()->getUsername();
                        $dbpass = $this->getDoctrine()->getConnection()->getPassword();

                        $pdo = new \PDO(
                            "mysql:host=$dbhost;dbname=$dbname",
                            $dbuser,
                            $dbpass,
                            array
                            (
                                \PDO::MYSQL_ATTR_LOCAL_INFILE => true,
                                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                            )
                        );
                    }
                    catch (\PDOException $e)
                    {
                        $this->addFlash('error','Помилка при додаванні каталога.' . $e->getMessage());
                        return $this->redirectToRoute('app_catalogs');
                    }

                    $affectedRows = $pdo->exec
                    (
                        "LOAD DATA LOCAL INFILE '$full_import_file_path'
INTO TABLE catalog_item
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n'
(catalog_id, number, code, price);"

                    );

                    if($affectedRows){
                        $catalog_created = true;

                        try {
                            if (file_exists($full_import_file_path)) {
                                unlink($full_import_file_path);
                            }

                            if (file_exists($full_path)) {
                                unlink($full_path);
                            }
                        } catch (\Error $e){
                            $this->addFlash('error','Помилка при додаванні каталога.' . $e);
                            return $this->redirectToRoute('app_catalogs');
                        }
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

    //TODO: Уточнити чи це взагалі треба
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

        // update downloads count
        $catalog->setDownloads($catalog->getDownloads() + 1);

        // Save
        $em = $this->getDoctrine()->getManager();
        $em->persist($catalog);
        $em->flush();

        $projectDir = $this->getParameter('kernel.project_dir');
        $csvStr = '';

        $full_export_file_path = $projectDir.'/public/uploads/'.'export_file_'.time().'.csv';

        // create tmp file
        file_put_contents($full_export_file_path, '');

        foreach ($catalog_items as $catalog_item){

            //TODO: Уточнити як формується ціна
            $price = floatval($catalog_item->getPrice());
            $price = $price * $extraCharge;

            $csvStr = '"'.$catalog_item->getNumber().'","'.$catalog_item->getCode().'","'.$price.'"' . "\n";

            file_put_contents($full_export_file_path, $csvStr, FILE_APPEND);
        }

        $response = new Response(file_get_contents($full_export_file_path));
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-disposition','attachment; filename="'.$catalog->getName().'"');

        if (file_exists($full_export_file_path)) {
            unlink($full_export_file_path);
        }

        return $response;
    }

    /**
     * @Route("/catalog/delete/{id}", name="app_delete_catalog")
     */
    public function delete(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getDoctrine()->getRepository(Catalog::class)->find($id);

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success','Каталог видалено!');
        return $this->redirectToRoute('app_catalogs');
    }


}
