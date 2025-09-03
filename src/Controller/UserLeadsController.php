<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Leads;
use App\Entity\Enterprise;
use App\Entity\User;

/**
 * Description of UserLeadsController
 *
 * @author Dev
 */
class UserLeadsController extends AbstractController {

    //put your code here

    public function __construct(private Security $security) {}

    public function __invoke(EntityManagerInterface $entityManager) {
        $user = $this->security->getUser();
        $user_loggedin_infos = $entityManager->getRepository(User::class)->find($user->getId());
        $user_loggedin_enterprise_infos = $entityManager->getRepository(Enterprise::class)->find($user_loggedin_infos->getEnterprise()->getId());
        
        $user_leads_list = $entityManager->getRepository(Leads::class)->findBy(["enterprise"=>$user_loggedin_infos->getEnterprise()->getId()]);
        
        return $user_leads_list;
    }
}
