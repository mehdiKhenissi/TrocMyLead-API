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
use App\ApiResource\CustomFunctions;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Description of LitigeController
 *
 * @author Dev
 */
class LeadController extends AbstractController {

    private $entityManager;
    private $customFunctions;

    public function __construct(private Security $security, EntityManagerInterface $entityManager, CustomFunctions $customFunctions) {
        $this->entityManager = $entityManager;
        $this->customFunctions = $customFunctions;
    }
    
    /**
     * 
     * @param Request $request
     * @param type $id
     * @return JsonResponse
     * UPDATE STATUT (to_sell, reserved, valid...) OF LEAD
     * 
     */
    #[Route(path: '/api/status/lead/{id}', name: 'update_status_lead', methods: ['PATCH'])]
    public function updateStatusLead(Request $request, $id) {
        $response_data = [];

        try {
            if (($this->security->getToken() !== null) && ( $this->security->isGranted("ROLE_ADMIN") || $this->security->isGranted("ROLE_USER") )) {
                $user_infos = $this->entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
                // check if user infos good
                if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {
                    $post_data = json_decode($request->getContent(), true);
                    if (isset($post_data['status'])) {
                        $lead_to_update = $this->entityManager->getRepository(Leads::class)->find($id);

                        if (!empty($lead_to_update)) {
                            $lead_to_update->setStatus($post_data['status']);
                            if ($post_data['status'] == 'valid') {
                                $lead_to_update->setValidatedAt(\DateTimeImmutable::createFromMutable(\DateTime::createFromFormat('Y-m-d H:i:s', date("Y-m-d H:i:s"))));
                            }
                            $this->entityManager->flush();

                            $response_data = ['code' => 200, 'id_lead_updated' => $lead_to_update->getId()];
                        } else {
                            $response_data = ['code' => 401, 'status' => 'error', 'message' => "Lead not found"];
                        }
                    } else {
                        $response_data = ['code' => 401, 'status' => 'error', 'message' => " Donnée 'Status' manquants, "];
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

    /**
     * 
     * @param Request $request
     * @param type $id
     * GET LISt OF LEADS OF SPECIFIC USER
     * 
     */
    #[Route(path: '/api/leads/user/{id}', name: 'current_user_leads', methods: ['GET'])]
    public function getListUserLeads(Request $request, $id) {
        $response_data = [];
        dd('getListUserLeads');

        $response = new JsonResponse($response_data);
        return $response;
    }

    /**
     * Calcule prix lead avec chatgpt API
     */
    #[Route(path: '/api/leads/price', name: 'chatgpt_price_lead', methods: ['POST'])]
    public function chatGptLeadPrice(Request $request) {
        $response_data = [];
        $session = new Session();

        try {

            if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
                $user_infos = $this->entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
                // check if user infos good
                if ((!empty($user_infos)) && ( ($user_infos->getDisabledAt() === null) || ( $user_infos->getDisabledAt()->getTimestamp() > strtotime(date("d-m-Y h:i:s")) ) )) {
                    $waited_inputs = ['activity', 'commentary'];
                    $post_data = json_decode($request->getContent(), true);
                    $check_post_data = $this->customFunctions->checkAllInArray($post_data, $waited_inputs);
                    //check if post data is good
                    if ($check_post_data === true) {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions'); // URL to fetch
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);     // Return the transfer as a string
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(
                                        [
                                            "model" => 'gpt-3.5-turbo-1106',
                                            "response_format" => ["type" => 'json_object'],
                                            "seed" => 122011,
                                            "messages" => [
                                                [
                                                    "role" => 'system',
                                                    "content" =>
                                                    'Ignore les précédentes instructions. Tu incarneras un deviseur de ' . $post_data['activity'] .
                                                    '. Toutes tes réponses devront être au format JSON et uniquement au format JSON suivant : {"min":[amount,amount,amount], "max":[amount,amount,amount]}, sans aucun texte d\'accompagnement et sans retour à la ligne et dans 1 seul et unique JSON. Ta tache sera d\'estimer 3 prix minimum et 3 prix maximum à la tache en France.'
                                                ],
                                                [
                                                    "role" => 'user',
                                                    "content" => $post_data['commentary']
                                                ]
                                            ]
                                        ]
                        ));
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json',
                            ]);
                        $response = curl_exec($ch);
                        if (curl_errno($ch)) {
                            $response_data = ['code' => 400, 'status' => 'error', 'message' => "Erreur API de calcule de prix"];
                        } else {
                            //dd(json_decode($response));
                            if(isset(json_decode($response)->id)){
                                $prices = json_decode(json_decode($response)->choices[0]->message->content);
                                $price_min = (($prices->min[0] + $prices->max[0]) / 2) * 0.01;
                                $price_max = (($prices->min[2] + $prices->max[2]) / 2) * 0.01;
                                
                                $session->set(md5(trim($post_data['activity'] .' : '. $post_data['commentary'])), ["price_min"=>$price_min, "price_max"=>$price_max]);
                                //dd($session->all());
                                        
                                $response_data = ['code' => 200, 'status' => 'success', 'price_informations' => json_decode($response)];
                            }
                            else{
                                $response_data = ['code' => 400, 'status' => 'error', 'message' => "Erreur API de calcule de prix"];
                            }
                        }
                        curl_close($ch);
                    } else {
                        $response_data = ['code' => 400, 'status' => 'error', 'message' => "Erreur post data, variable manquante (" . $check_post_data . ")"];
                    }
                } else {
                    $response_data = ['code' => 400, 'status' => 'error', 'message' => "Utilisateur invalide"];
                }
            } else {
                $response_data = ['code' => 400, 'status' => 'error', 'message' => "Invalid token"];
            }
        } catch (\Throwable $ex) {
            //return $ex->getMessage();
            $response_data = ['code' => 400, 'status' => 'error', 'message' => $ex->getMessage()];
        }

        return new JsonResponse($response_data);
    }
}
