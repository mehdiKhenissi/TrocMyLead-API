<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Leads;
use App\Entity\Sell;
use App\Entity\Enterprise;
use ZipArchive;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Description of LitigeController
 *
 * @author Dev
 */
class CustomController extends AbstractController {

    private $entityManager;

    public function __construct(private Security $security, EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/api/test', name: 'test_api', methods: ['GET'])]
    public function testApiFunction(Request $request) {
        try {
            $enterprise_list = [];
            $session = new Session();

            // header pour dire que la réponse est de type JSON
            //header('Content-Type: application/json');

            //header('Access-Control-Allow-Credentials: true');
            // Autorise l'acces quelque soit le domaine de CORS ou non
            //header("Access-Control-Allow-Origin: http://localhost:5173");
            // Autorise les méthodes HTTP nécessaires (GET, POST, OPTIONS)
            //header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

            // Autorise les en-têtes nécessaires, y compris Content-Type
            //header('Access-Control-Allow-Headers: Content-Type, Authorization');

            //$session->clear();
            //$session->set("oku", ["price_min"=>10, "price_max"=>20]);
            //dd(ini_get('session.gc_maxlifetime'));
            //dd();
            $response = new JsonResponse($session->all());
        }
        catch (\Throwable $ex) {
            $response = new JsonResponse($ex->getMessage());
        }

        return $response;
    }

    #[Route(path: '/api/testous/testous', name: 'enterprises_testous_api', methods: ['GET'])]
    public function enterpriseTestApiFunction(Request $request) {
        $enterprise_list = [];
        $enterprise_infos = $this->entityManager->getRepository(Enterprise::class)->findAll();

        foreach ($enterprise_infos as $enterprise_item) {
            array_push($enterprise_list, ["id" => $enterprise_item->getId()]);
        }

        $response = new JsonResponse($enterprise_list);
        return $response;
    }

    /**
     * 
     * @param Request $request
     * @param type $id
     * @return JsonResponse
     * UPDATE STATUT (to_sell, reserved, valid...) OF LEAD
     * 
     */
    #[Route(path: '/api/download/invoices', name: 'download_multiple_invoices', methods: ['GET'])]
    public function dwonloadMultipleInvoices(Request $request) {
        $response_data = [];

        //return new JsonResponse($response_data);

        try {
            if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
                $user_infos = $this->entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
                // check if user infos good
                if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {

                    if (($request->query->get('date_from') != null) && ( ($request->query->get('date_to')) != null )) {
                        $date_from = $request->query->get('date_from');
                        $date_to = $request->query->get('date_to');
                        if (( \DateTime::createFromFormat('Y-m-d H:i', ($date_from)) ) && ( \DateTime::createFromFormat('Y-m-d H:i', ($date_to)) )) {
                            $user_leads_buyed_selled_query = $this->entityManager->getRepository(Sell::class)->findValidBuyedSelledFromToDate($user_infos->getEnterprise()->getId(), $date_from, $date_to);
                            //dd($user_leads_buyed_selled_query);

                            /* ZIP AND DOWNLOAD */

                            // The name of the ZIP file you'll be creating
                            $zipFileName = 'myFiles.zip';

                            // Initialize the ZIP file
                            $zip = new ZipArchive();

                            // Open the file to insert items into it
                            if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
                                // Add files to the ZIP file
                                // Specify the path to the files on your server
                                foreach ($user_leads_buyed_selled_query as $leads_buyed_selled_item) {
                                    if (file_exists($this->getParameter('enterprise_files_directory') . 'enterprise_' . $leads_buyed_selled_item->getBuyerEnterprise()->getId() . '/invoices/' . $leads_buyed_selled_item->getinvoiceNum() . ".pdf")) {
                                        $zip->addFile($this->getParameter('enterprise_files_directory') . 'enterprise_' . $leads_buyed_selled_item->getBuyerEnterprise()->getId() . '/invoices/' . $leads_buyed_selled_item->getinvoiceNum() . ".pdf", $leads_buyed_selled_item->getinvoiceNum() . ".pdf");
                                    }
                                }

                                // You can add as many files as you need
                                // Close the ZIP file after adding all files
                                $zip->close();

                                if (file_exists($zipFileName)) {
                                    header('Access-Control-Allow-Origin: *');
                                    // Initiate the download
                                    // Set headers to indicate the response is a downloadable file
                                    header('Content-Type: application/zip');
                                    header('Content-Disposition: attachment; filename="' . basename($zipFileName) . '"');
                                    header('Content-Length: ' . filesize($zipFileName));

                                    // Clear any previous output
                                    ob_clean();
                                    flush();

                                    // Read the ZIP file and send it to the output buffer
                                    readfile($zipFileName);

                                    // After download delete the ZIP file from the server
                                    unlink($zipFileName);

                                    exit;
                                }
                            } else {
                                //echo 'Failed to create the ZIP file.';
                                $response_data = ['code' => 400, "status" => "erreur", "message" => 'Failed to create the ZIP file.'];
                            }

                            /* END ZIP AND DOWNLOAD */
                        } else {
                            $response_data = [
                                'code' => 400,
                                'status' => 'error',
                                'message' => ((!(\DateTime::createFromFormat('Y-m-d H:i', ($date_from)) )) ? ' Erreur fromat "date_from" (YYYY-mm-dd H:i) ' : '') . ' ' . ((!(\DateTime::createFromFormat('Y-m-d H:i', ($date_to)) )) ? ' Erreur fromat "date_to" (YYYY-mm-dd H:i) ' : '')
                            ];
                        }
                    } else {
                        $response_data = [
                            'code' => 400,
                            'status' => 'error',
                            'message' => ( ($request->query->get('date_from') == null) ? ' Variable "date_from" manquants ' : '') . ' ' . ( ($request->query->get('date_to') == null) ? ' Variable "date_to" manquants ' : '')
                        ];
                    }
                } else {
                    $response_data = ['code' => 401, 'status' => 'error', 'message' => "Utilisateur invalid"];
                }
            } else {
                $response_data = ['code' => 401, 'status' => 'error', 'message' => "Invalid token"];
            }
        } catch (Error $e) {
            $response_data = ['code' => 401, 'status' => 'error', 'message' => $e->getMessage()];
            //echo json_encode(['error' => $e->getMessage()]);
        }

        $response = new JsonResponse($response_data);
        return $response;
    }

    #[Route(path: '/api/download/invoice/{id_sell}', name: 'download_invoice', methods: ['GET'])]
    public function downloadInvoice(Request $request, $id_sell) {
        header('Access-Control-Allow-Origin: *');
        $response_data = [];

        try {
            if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
                $user_infos = $this->entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
                // check if user infos good
                if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {
                    $sell_infos_result = $this->entityManager->getRepository(Sell::class)->find($id_sell);
                    $invoice_path = $this->getParameter('enterprise_files_directory') . 'enterprise_' . $sell_infos_result->getBuyerEnterprise()->getId() . '/invoices/' . $sell_infos_result->getinvoiceNum() . ".pdf";
                    if (file_exists($invoice_path)) {
                        // Définissez les en-têtes pour forcer le téléchargement
                        header('Content-Description: File Transfer');
                        header('Content-Type: application/pdf');
                        header('Content-Disposition: attachment; filename="' . basename($invoice_path) . '"');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($invoice_path));

                        ob_clean(); // Clear any previous output
                        flush(); // Vide les tampons de sortie du système
                        readfile($invoice_path); // Lit le fichier et l'envoie au navigateur
                        exit;
                    } else {
                        $response_data = ['code' => 400, "status" => "erreur", "message" => 'Invoice not found.'];
                    }
                } else {
                    $response_data = ['code' => 401, 'status' => 'error', 'message' => "Utilisateur invalid"];
                }
            } else {
                $response_data = ['code' => 401, 'status' => 'error', 'message' => "Invalid token"];
            }
        } catch (\Throwable $ex) {
            $response_data = ['code' => 401, 'status' => 'error', 'message' => $ex->getMessage()];
        }

        $response = new JsonResponse($response_data);
        return $response;
    }
}
