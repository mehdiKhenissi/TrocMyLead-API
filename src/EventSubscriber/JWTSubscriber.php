<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Enterprise;

class JWTSubscriber implements EventSubscriberInterface {

    private $entityManager;

    public function __construct(
            EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function onLexikJwtAuthenticationOnJwtCreated($event): void {
        $data = $event->getData();
        $data['username'] = $event->getUser()->getEmail();

        //dd( !is_null($event->getUser()->getdisabledAt()) );

        if (!is_null($event->getUser()->getdisabledAt())) {//date token expired at = date user disabled at, if not null
            $disabledAtDateTime = new \DateTime(($event->getUser()->getdisabledAt())->format('Y-m-d H:i:s'));
            $data['exp'] = strtotime($disabledAtDateTime->format('Y-m-d H:i:s'));
        } else {
            $data['exp'] = strtotime(date("Y-m-d H:i:s", strtotime("+1 day")));
        }

        $event->setData($data);
    }

    // CUSTOM JWT AUTHHENTICATION RESPONSE
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event) {
        /* $data = $event->getData();
          $response = $event->getResponse();
          // Customize the response data as needed
          $data['custom_key'] = 'custom_value';
          // Create a new JsonResponse with the modified data
          $newResponse = new JsonResponse($data, $response->getStatusCode());
          // Set the new response
          //$event->setResponse($newResponse);
          dd($event->getResponse()->getContent()); */

        $user = $event->getUser();

        //check if enterprise user is not disabled
        if (!is_null($user->getEnterprise())) {
            $infos_enterprise_query = $this->entityManager->createQuery('SELECT e FROM App\Entity\Enterprise e where e.id = ' . $user->getEnterprise()->getId());
            $infos_enterprise_result = $infos_enterprise_query->getOneOrNullResult();
            if (!empty($infos_enterprise_result)) {

                if (( is_null($infos_enterprise_result->getEnabledAt()))) {
                    $event->setData(["code" => 448, "message" => "Entreprise invalide."]);
                } elseif ((!is_null($infos_enterprise_result->getDisabledAt()))) {
                    $enterpriseDisabledAtDateTime = new \DateTime(($infos_enterprise_result->getDisabledAt())->format('Y-m-d H:i:s'));
                    if ($enterpriseDisabledAtDateTime->format('Y-m-d H:i:s') < date('Y-m-d H:i:s')) {
                        $event->setData(["code" => 401, "message" => "Entreprise désactivé."]);
                    }
                    else{
                        goto checkUserEnabled;
                    }
                } 
                else {
                    checkUserEnabled:
                    //check if user is not disabled
                    if (is_null($user->getEnabledAt())) {//if user is not enabled
                        $event->setData(["code" => 449, "message" => "Veuillez saisir le code de validation pour activer votre compte."]);
                    } 
                    elseif (!is_null($user->getdisabledAt())) { // if user is disabled
                        //$response = new JsonResponse(["code" => 401, "message" => "User disabled."], JsonResponse::HTTP_FORBIDDEN);
                        $userDisabledAtDateTime = !is_null($user->getdisabledAt()) ? (new \DateTime(($user->getdisabledAt())->format('Y-m-d H:i:s')))->format('Y-m-d H:i:s') : null;
                        if ($userDisabledAtDateTime < date('Y-m-d H:i:s')) { //if disbaled at date inferieur to date now
                            $event->setData(["code" => 401, "message" => "Utilisateur désactivé."]);
                        }
                    }
                }
            } else {
                $event->setData(["code" => 404, "message" => "Entreprise introuvable."]);
            }
        } else {
            // check if user is admin, so have no enterprise, so auth is good and no error
            if(!in_array('ROLE_ADMIN', $user->getRoles())){
                $event->setData(["code" => 404, "message" => "Entreprise introuvable."]);
            }
            
        }



        //dd($event->getData());
    }

    public static function getSubscribedEvents(): array {
        return [
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthenticationSuccessResponse',
            'lexik_jwt_authentication.on_jwt_created' => 'onLexikJwtAuthenticationOnJwtCreated',
        ];
    }
}
