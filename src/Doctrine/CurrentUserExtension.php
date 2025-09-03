<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Enterprise;
use App\Entity\Sell;
use App\Entity\User;
use App\Entity\Litige;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use App\Entity\UserOwnedInterface;

/**
 * Description of CurrentUserExtension
 *
 * @author Dev
 */
class CurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface {

    //put your code here

    public function __construct(private Security $security, private EntityManagerInterface $entityManager) {
        
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, \ApiPlatform\Metadata\Operation $operation = null, array $context = []): void {
        $this->addWhere($queryBuilder, $resourceClass, $operation);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, \ApiPlatform\Metadata\Operation $operation = null, array $context = []): void {
        //dd($operation);
        $this->addWhere($queryBuilder, $resourceClass, $operation);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass, $operation) {
        $reflectionClass = new \ReflectionClass($resourceClass);
        $alias = $queryBuilder->getRootAliases()[0];

        if ($this->security->getUser()) {
            $current_user_infos = $this->entityManager->getRepository(User::class)->find($this->security->getUser()->getId());

            if (!in_array("ROLE_ADMIN", $current_user_infos->getRoles())) {

                if ($resourceClass == Enterprise::class) {// limiter l'utilisateur connecter à gerer que l'informations de l'entreprise à qui il appartient
                    $queryBuilder->andWhere("$alias.id = :current_user_enterprise_id")
                            ->setParameter('current_user_enterprise_id', $current_user_infos->getEnterprise()->getId());
                }
                elseif ($reflectionClass->implementsInterface(UserOwnedInterface::class)) {

                    if ($resourceClass == Sell::class) {
                        $queryBuilder->andWhere("$alias.buyer_enterprise = :current_user_enterprise_id")
                                ->setParameter('current_user_enterprise_id', $current_user_infos->getEnterprise()->getId()); // => this generate probléme on create sell because its check if buyer_buyer enteprrise exist and equal to current enteperise id
                    } elseif ($resourceClass == Litige::class) {
                        $queryBuilder->innerJoin("$alias.lead", 'le', 'WITH', "le.id = $alias.lead")
                                ->innerJoin("le.sell", 'sel', 'WITH', "le.id = sel.lead")
                                ->andWhere('le.enterprise = :current_user_enterprise_id')
                                ->orWhere("sel.buyer_enterprise = :current_user_enterprise_id")
                                ->setParameter('current_user_enterprise_id', $current_user_infos->getEnterprise()->getId());
                    } else {
                        $queryBuilder->andWhere("$alias.enterprise = :current_user_enterprise_id")
                                ->setParameter('current_user_enterprise_id', $current_user_infos->getEnterprise()->getId());
                    }
                    
                }
                
            }
            
        }
    }
}
