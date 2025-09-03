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
use App\Entity\Litige;
use App\ApiResource\CustomFunctions;
use App\Entity\Leads;
use App\Entity\User;

/**
 * Description of LitigeController
 *
 * @author Dev
 */
class LitigeController extends AbstractController {

    private $customFunctions;
    private $entityManager;

    public function __construct(private Security $security, EntityManagerInterface $entityManager, CustomFunctions $customFunctions) {
        $this->customFunctions = $customFunctions;
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/api/litiges/lead/{id}', name: 'sells_by_lead', methods: ['GET'])]
    public function litigesByLead(Request $request, EntityManagerInterface $entityManager, $id) {
        
    }

    #[Route(path: '/api/litiges', name: 'custom_add_new_litige', methods: ['POST'])]
    public function customAddNewLitige(Request $request) {
        $new_litige = new Litige();
        $response_data = [];
        $post_data = json_decode($request->getContent(), true);

        try {

            if (($this->security->getToken() !== null) && ($this->security->isGranted("ROLE_USER"))) {
                /* check post data to insert */
                $waited_inputs = ['commentary', 'status', 'lead'];
                $post_data = json_decode($request->getContent(), true);
                $check_post_data = $this->customFunctions->checkAllInArray($post_data, $waited_inputs);

                if ($check_post_data === true) {
                    //Lead
                    $lead_litiged = $this->entityManager->getRepository(Leads::class)->find($post_data['lead']);
                    if (!empty($lead_litiged)) {

                        $new_litige->setCommentary($post_data['commentary'])
                                ->setLead($lead_litiged)
                                ->setStatus($post_data['status']);

                        $this->entityManager->persist($new_litige);
                        $this->entityManager->flush();
                        $this->entityManager->clear();

                        //update lead row
                        $lead_litiged->setStatus("checking");
                        $this->entityManager->merge($lead_litiged);
                        $this->entityManager->flush();

                        if ($new_litige->getId()) {
                            // admins list
                            $admins_list = $this->entityManager->getRepository(User::class)->findBy(['enterprise' => null]);

                            foreach ($admins_list as $admin_item) {
                                //send email of litige
                                $this->customFunctions->sendEmail(
                                        $admin_item->getEmail(),
                                        '📜 Nouveau litige, LEAD #' . $post_data['lead'] . '#',
                                        'Un litige a été créer concernant le lead #' . $post_data['lead'] . '#, ci-dessous la description du litige:<br/>' . $post_data['commentary'] . '<br/><br/> Cordialement,'
                                );
                            }
                            
                        }

                        $response_data = ['code' => 200, "message" => "success", "id_litige" => $new_litige->getId()];
                    } else {
                        $response_data = ['code' => 400, "statut" => "error", "message" => "Les informations sur le lead introuvables"];
                    }
                } else {
                    $response_data = ['code' => 400, 'status' => 'error', 'message' => "Erreur post data, variable manquante (" . $check_post_data . ")"];
                }
            } else {
                $response_data = ['code' => 400, 'status' => 'error', 'message' => "Token not found"];
            }
        } catch (\Throwable $ex) {
            //dd($ex);
            $response_data = ['code' => 400, "message" => $ex->getMessage()];
        }


        return new JsonResponse($response_data);
    }
}
