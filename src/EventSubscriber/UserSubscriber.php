<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\ApiResource\CustomFunctions;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;

/**
 * Description of UserSubscriber
 *
 * @author Dev
 */
class UserSubscriber implements EventSubscriberInterface {

    private $globalParams;
    private $customFunctions;
    private Generator $faker;

    public function __construct(ParameterBagInterface $globalParams, CustomFunctions $customFunctions, private EntityManagerInterface $entityManager) {
        $this->globalParams = $globalParams;
        $this->customFunctions = $customFunctions;
        $this->faker = Factory::create('fr_FR');
    }

    public static function getSubscribedEvents() {
        return [
            KernelEvents::VIEW => [
                ['onPreWrite', EventPriorities::PRE_WRITE],
                ['onPostCreate', EventPriorities::POST_WRITE],
                ],
        ];
    }
    
    public function onPreWrite(ViewEvent $event)
    {   
        $entity = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        
        // Ensure that the entity is of the type you're interested in and the HTTP method is POST
        if(($entity instanceof User) && (Request::METHOD_POST === "POST")){
            //$entity->setCodeValidation($this->faker->word() .$this->faker->randomNumber(3).$this->faker->word());
            $entity->setCodeValidation($this->faker->safeColorName() . $this->faker->randomNumber(3) . $this->faker->safeColorName());
        }
        
    }

    public function onPostCreate(ViewEvent $event): void {
        $entity = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();
        
        // Ensure that the entity is of the type you're interested in and the HTTP method is POST
        if (($entity instanceof User) && ($method === Request::METHOD_POST)) {
            $enterprise_dir = $this->globalParams->get('enterprise_files_directory') . 'enterprise_' . $entity->getEnterprise()->getId() . '/';

            if (!empty(glob($enterprise_dir . 'cni_file.*'))) {
                $cni_file = glob($enterprise_dir . 'cni_file.*')[0];
            }

            if (!empty(glob($enterprise_dir . 'kbis_file.*'))) {
                $kbis_file = glob($enterprise_dir . 'kbis_file.*')[0];
            }

            // admins list
            $admins_list = $this->entityManager->getRepository(User::class)->findBy(['enterprise' => null]);
            foreach ($admins_list as $admin_item) {
                // send email to admin
                $email_admin_content = 'Bonjour, <br/> '
                        . 'Une nouvelle inscription sur l\'application TrocMonLead  a été effectuer, vous trouverez ci-joint les documents de l\'entreprise et ci_dessous les informations du nouveau utilisateur <br/>'
                        . '<b>- Prénom & nom : </b>' . $entity->getFirstname() . ' ' . $entity->getName() . ' <br/> '
                        . '<b>- Email : </b>' . $entity->getEmail() . ' <br/> '
                        . '<b>- Téléphone : </b> +33' . $entity->getPhone() . ' <br/> ';
                
                $this->customFunctions->sendEmail($admin_item->getEmail(), "TrocMonLead : Nouvelle inscription", $email_admin_content, [$cni_file, $kbis_file]);
            }


            //send email to user created
            $email_user_created_content = 'Bonjour, <br/> '
                    . 'Bienvenue sur la platforme TrocMonLead, votre compte a été crée avec succès<br/>'
                    . '<b>- Prénom & nom : </b>' . $entity->getFirstname() . ' ' . $entity->getName() . ' <br/> '
                    . '<b>- Email : </b>' . $entity->getEmail() . ' <br/> '
                    . '<b>- Téléphone : </b> +33' . $entity->getPhone() . ' <br/> '
                    . '<br/><b style="color:red;">Veuillez utiliser le  code de validation suivant à votre premier connection pour activer votre compte : </b>' . $entity->getCodeValidation() . ' <br/> ';
            $this->customFunctions->sendEmail($entity->getEmail(), "TrocMonLead : Confirmation de création de compte", $email_user_created_content, [$cni_file, $kbis_file]);
        }

        // Place your custom logic here
        // For example, sending an email, logging, etc.
    }
    
}
