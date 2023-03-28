<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class PaymentService
{
    private const PAYMENT_TYPE = 1;

    private const DEPOSIT_TYPE = 2;

    private EntityManagerInterface $entityManager;

    private TransactionRepository $transactionRepository;

    public function __construct(EntityManagerInterface $entityManager, TransactionRepository $transactionRepository)
    {
        $this->entityManager = $entityManager;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @throws Exception
     */
    public function deposit(User $user, float $amount): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $transaction = new Transaction();
            $transaction->setCustomer($user);
            $transaction->setType(self::DEPOSIT_TYPE);
            $transaction->setAmount($amount);
            $user->setBalance($user->getBalance() + $amount);

            $transaction->setCreated(new DateTimeImmutable());

            $this->transactionRepository->save($transaction, true);

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw new \RuntimeException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @throws Exception
     */
    public function payment(User $user, Course $course): Transaction
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            if ($user->getBalance() < $course->getPrice()) {
                throw new \RuntimeException('На счету недостаточно средств.', Response::HTTP_NOT_ACCEPTABLE);
            }
            $transaction = new Transaction();

            $transaction->setCustomer($user);
            $transaction->setType(self::PAYMENT_TYPE);
            $transaction->setAmount($course->getPrice());
            $transaction->setCourse($course);

            if ($course->getType() === 'rent') {
                $transaction->setExpires((new DateTimeImmutable())->add(new DateInterval('P1W'))); // one week
            }

            $transaction->setCreated(new DateTimeImmutable());

            $user->setBalance($user->getBalance() - $course->getPrice());

            $this->transactionRepository->save($transaction, true);

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw new \RuntimeException($exception->getMessage(), $exception->getCode());
        }
        return $transaction;
    }
}
