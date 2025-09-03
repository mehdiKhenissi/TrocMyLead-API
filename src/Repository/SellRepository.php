<?php

namespace App\Repository;

use App\Entity\Sell;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

/**
 * @extends ServiceEntityRepository<Sell>
 *
 * @method Sell|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sell|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sell[]    findAll()
 * @method Sell[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SellRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Sell::class);
    }

    public function findAllJoinLeadEnterprise($enterprise_id, $page = 1, $nb_results = null) {
        $max_results = $nb_results;
        $first_result = ($page > 1) ? ( ($page - 1) * $max_results) : 0;

        $query = $this->createQueryBuilder('s')
                ->innerJoin('s.lead', 'l', 'WITH', 'l.id = s.lead')
                ->andWhere('l.enterprise = :val')
                ->setParameter('val', $enterprise_id)
                ->orWhere('s.buyer_enterprise = :val')
                ->setParameter('val', $enterprise_id)
                ->setFirstResult($first_result);

        if ($max_results != null) {
            $query->setMaxResults($max_results);
        }

        return $query->getQuery()->getResult();
    }

    public function findValidBuyedSelledFromToDate($enterprise_id, $date_from, $date_to, $format_array=false) {
        //\DateTime::createFromFormat('Y-m-d H:i:s', $date_from)->format('Y-m-d H:i:s')
        $query = $this->createQueryBuilder('s')
                ->innerJoin('s.lead', 'l', 'WITH', 'l.id = s.lead')
                ->andWhere('l.enterprise = :val')
                ->setParameter('val', $enterprise_id)
                ->orWhere('s.buyer_enterprise = :val')
                ->setParameter('val', $enterprise_id)
                ->andWhere('l.status = :status ')
                ->setParameter('status', "valid")
                ->andWhere('s.created_at >= :date_from ')
                ->setParameter('date_from', $date_from)
                ->andWhere('s.created_at <= :date_to ')
                ->setParameter('date_to', $date_to);

        return $query->getQuery()->getResult($format_array?\Doctrine\ORM\Query::HYDRATE_ARRAY:null);
    }


//    /**
//     * @return Sell[] Returns an array of Sell objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }
//    public function findOneBySomeField($value): ?Sell
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
