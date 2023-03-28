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

    public function findUserTransactionsWithFilters(User $user, array $filters): array
    {
        $query = $this->createQueryBuilder('t')
            ->leftJoin('t.course', 'c')
            ->andWhere('t.customer = :user')
            ->setParameter('user', $user->getId())
            ->orderBy('t.created');

        if (!is_null($filters['type'])) {
            $query->andWhere('t.type = :type')->setParameter('type', $filters['type']);
        }

        if (!is_null($filters['course_code'])) {
            $query->andWhere('c.code = :code')->setParameter('code', $filters['course_code']);
        }

        if (!is_null($filters['skip_expired'])) {
            $query->andWhere('t.expires IS NULL OR t.expires >= :today')
                ->setParameter('today', new DateTimeImmutable());
        }
        return $query->getQuery()->getResult();
    }
}
