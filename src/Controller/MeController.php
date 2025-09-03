<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Enterprise;


class MeController extends AbstractController
{

    /*#[Route(path:'/api/me', name:'user_infos', methods:['GET'])]
    public function userInfos(){
        $user = $this->getUser();
        return $this->json([
            $user
        ]);
    }*/

    public function __construct(private Security $security){}

    public function __invoke(EntityManagerInterface $entityManager){
        $user = $this->security->getUser();
        $user_loggedin_infos = $entityManager->getRepository(User::class)->find($user->getId());
        //dd($user_loggedin_infos->getMain());
        //$user_loggedin_enterprise_infos = $entityManager->getRepository(Enterprise::class)->find($user_loggedin_infos->getEnterprise()->getId());
        
        $user->setFirstName($user_loggedin_infos->getFirstName())
                ->setName($user_loggedin_infos->getName())
                ->setPhone($user_loggedin_infos->getPhone())
                ->setRoles($user_loggedin_infos->getRoles())
                ->setMain($user_loggedin_infos->getMain())
                ->setEnterprise(
                           $user_loggedin_infos->getEnterprise() ? $entityManager->getRepository(Enterprise::class)->find($user_loggedin_infos->getEnterprise()->getId()) : null
                        );
      
        return $user;
    }

}