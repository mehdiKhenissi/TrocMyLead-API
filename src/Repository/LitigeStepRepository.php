<?php

namespace App\Repository;

use App\Entity\LitigeStep;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LitigeStep>
 *
 * @method LitigeStep|null find($id, $lockMode = null, $lockVersion = null)
 * @method LitigeStep|null findOneBy(array $criteria, array $orderBy = null)
 * @method LitigeStep[]    findAll()
 * @method LitigeStep[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LitigeStepRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LitigeStep::class);
    }

//    /**
//     * @return LitigeStep[] Returns an array of LitigeStep objects
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

//    public function findOneBySomeField($value): ?LitigeStep
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
