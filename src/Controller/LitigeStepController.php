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
use App\Entity\LitigeStep;
use App\ApiResource\CustomFunctions;
use App\Entity\Leads;

/**
 * Description of LitigeController
 *
 * @author Dev
 */
class LitigeStepController extends AbstractController {

    private $customFunctions;
    private $entityManager;

    public function __construct(private Security $security, EntityManagerInterface $entityManager, CustomFunctions $customFunctions) {
        $this->customFunctions = $customFunctions;
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/api/litige_steps/litige/{id}', name: 'litige_steps_by_litige_id', methods: ['GET'])]
    public function litigesByLead(Request $request, EntityManagerInterface $entityManager, $id) {
        $response_data = [];

        try {
            if (($this->security->getToken() !== null) && ( $this->security->isGranted("ROLE_ADMIN") || $this->security->isGranted("ROLE_USER"))) {
                if (is_numeric($id)) {
                    $list_litige_step = $this->entityManager->getRepository(LitigeStep::class)->FindBy(["litige" => $id]);
                    //dd($list_litige_step);
                    $list_litige_step_array = array_map(function ($litige_step) {
                        return [
                            'id' => $litige_step->getId(),
                            'step' => $litige_step->getStep(),
                            'commentary' => $litige_step->getCommentary(),
                            'created_at' => $litige_step->getCreatedAt()->format('Y-m-d H:i:s')
                        ];
                    }, $list_litige_step);

                    
                    $response_data = ['code' => 200, 'litige_steps_datas' => $list_litige_step_array];
                } else {
                    $response_data = ['code' => 400, 'status' => 'error', 'message' => "Erreur Litige ID"];
                }
            } else {
                $response_data = ['code' => 400, 'status' => 'error', 'message' => "Token not found"];
            }
        } catch (\Throwable $ex) {
            $response_data = ['code' => 400, "message" => $ex->getMessage()];
        }

        return new JsonResponse($response_data);
    }

}
