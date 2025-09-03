<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Entity\Leads;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\ApiResource\CustomFunctions;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Description of UserSubscriber
 *
 * @author Dev
 */
class LeadSubscriber implements EventSubscriberInterface {

    private $globalParams;
    private $customFunctions;
    private Generator $faker;
    private $session;

    public function __construct(ParameterBagInterface $globalParams, CustomFunctions $customFunctions, private EntityManagerInterface $entityManager) {
        $this->globalParams = $globalParams;
        $this->customFunctions = $customFunctions;
        $this->faker = Factory::create('fr_FR');
        $this->session = new Session();
    }

    public static function getSubscribedEvents() {
        return [
            KernelEvents::VIEW => [
                ['onPreCreate', EventPriorities::PRE_WRITE],
                ],
        ];
    }
    
    /**
     *  cette fon,ction est executer dans le cas de PATCH et POST
     * @param ViewEvent $event
     * @return void
     */
    public function onPreCreate(ViewEvent $event): void {
        $entity = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        
        // Ensure that the entity is of the type you're interested in and the HTTP method is POST
        if (($entity instanceof Leads) && ( in_array($method, [Request::METHOD_POST, Request::METHOD_PATCH]) ) ) {
            
             if( $this->session->has(md5($entity->getActivity().' : '.$entity->getCommentary())) ){
                $prices_chatgpt = $this->session->get(md5($entity->getActivity().' : '.$entity->getCommentary()));
                if( ($entity->getPricingToSeller() < $prices_chatgpt['price_min']) || ($entity->getPricingToSeller() > $prices_chatgpt['price_max'])){
                    $event->setResponse(new JsonResponse(['code'=>400, 'status' => 'error', 'message' => "Le prix du Lead n'est pas dans l'intervalle des prix attendus"]));
                }
            }
            else{
                $event->setResponse(new JsonResponse(['code'=>400, 'status' => 'error', 'message' => "Les prix calculés via l'api Chatgpt sont introuvables."]));
            }
            
        }

    }
    
}
