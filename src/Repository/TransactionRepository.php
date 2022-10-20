<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function save(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findUserTransactionsWithFilters(User $user, array $filters)
    {
        $query = $this->createQueryBuilder('t')
            ->leftJoin('t.course', 'c')
            ->andWhere('t.customer = :user')
            ->setParameter('user', $user->getId())
            ->orderBy('t.created');

        if (!is_null($filters['type'])) {
            $query->andWhere('t.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if (!is_null($filters['course_code'])) {
            $query->andWhere('c.code = :course')
                ->setParameter('course', $filters['course_code']);
        }

        if (!is_null($filters['skip_expired'])) {
            $query->andWhere('t.expires IS NULL OR t.expires >= :today')
                ->setParameter('today', new DateTimeImmutable());
        }

        return $query->getQuery()->getResult();
    }

    public function findTransactionsInLastMonth(DateTimeImmutable $startDate, DateTimeImmutable $endDate)
    {
        $entityManager = $this->getEntityManager();

        $query = 'SELECT c.title AS title,
                       c.type AS type,
                       sum(t.amount) AS total,
                       count(t.id) AS count
                FROM App\Entity\Transaction t,
                     App\Entity\Course c
                WHERE (c.type = 3 OR c.type = 1)
                  AND t.course = c.id
                  AND t.created BETWEEN :start AND :end
                GROUP BY c.title, c.type';
        $query = $entityManager->createQuery($query)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        return $query->getResult();
    }

    public function findExpiredTransactions()
    {
        $startDate = new DateTimeImmutable();
        $endDate = $startDate->modify('+1 day');

        $entityManager = $this->getEntityManager();

        $query = 'SELECT c.title AS title,
                       t.expires AS expires
                FROM App\Entity\Transaction t,
                     App\Entity\Course c
                WHERE (c.type = 3 OR c.type = 1)
                  AND t.course = c.id
                  AND t.expires BETWEEN :start AND :end';
        $query = $entityManager->createQuery($query)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        return $query->getResult();
    }
}
