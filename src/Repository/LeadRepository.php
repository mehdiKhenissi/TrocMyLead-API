<?php

namespace App\Repository;

use App\Entity\Leads;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Leads>
 *
 * @method Lead|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lead|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lead[]    findAll()
 * @method Lead[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Leads::class);
    }
    
    public function findLeadsByEnterprise($enterprise_id, $page, $nb_results) {
        $max_results = $nb_results;
        $first_result = ($page>1) ? ( ($page-1) * $max_results) : 0;
        
        return $this->createQueryBuilder('le')
                        ->andWhere('le.enterprise = :val')
                        ->setParameter('val', $enterprise_id)
                        ->setMaxResults($max_results)
                        ->setFirstResult($first_result)
                        ->getQuery()
                        ->getResult();
    }
    
    
    public function findLeadsReservedTwoDaysAgo() {
        
        return $this->createQueryBuilder('le')
                 ->innerJoin('le.sell', 'se', 'WITH', 'le.id = se.lead')
                        ->Where('le.status = :statusval')
                        ->setParameter('statusval', "reserved")
                ->andWhere('se.created_at < :createdatval')
                        ->setParameter('createdatval', date('Y-m-d H:i:s', strtotime('-2 days')))
                        ->getQuery()
                        ->getResult();
    }

//    /**
//     * @return Lead[] Returns an array of Lead objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Lead
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
