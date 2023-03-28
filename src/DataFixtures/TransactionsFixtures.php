<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TransactionsFixtures extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $userRepository = $manager->getRepository(User::class);
        $courseRepository = $manager->getRepository(Course::class);

        $user = $userRepository->findOneBy(['email' => 'user@study-on.ru']);
        $admin = $userRepository->findOneBy(['email' => 'admin@study-on.ru']);

        $rentedCourses = $courseRepository->findBy(['type' => 1]);
        $boughtCourses = $courseRepository->findBy(['type' => 3]);

        $transactions = [
            // deposit
            [
                'type' => 2,
                'amount' => 200,
                'customer' => $user,
                'created' => new DateTimeImmutable('2021-09-01 00:00:00'),
            ],
            [
                'type' => 2,
                'amount' => 2000,
                'customer' => $admin,
                'created' => new DateTimeImmutable('2021-10-01 00:00:00'),
            ],
            // buy
            [
                'type' => 1,
                'amount' => $boughtCourses[0]->getPrice(),
                'course' => $boughtCourses[0],
                'customer' => $user,
                'created' => new DateTimeImmutable('2022-10-08 00:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $boughtCourses[1]->getPrice(),
                'course' => $boughtCourses[1],
                'customer' => $admin,
                'created' => new DateTimeImmutable('2022-10-10 00:00:00'),
            ],
            // rent - expires
            [
                'type' => 1,
                'amount' => $rentedCourses[0]->getPrice(),
                'expires' => new \DateTimeImmutable('2024-09-27 00:00:00'),
                'course' => $rentedCourses[0],
                'customer' => $user,
                'created' => new \DateTimeImmutable('2022-09-20 00:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $rentedCourses[0]->getPrice(),
                'expires' => new \DateTimeImmutable('2022-10-17 00:00:00'),
                'course' => $rentedCourses[0],
                'customer' => $admin,
                'created' => new \DateTimeImmutable('2022-10-10 00:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $rentedCourses[1]->getPrice(),
                'expires' => new \DateTimeImmutable('2022-09-17 00:00:00'),
                'course' => $rentedCourses[1],
                'customer' => $user,
                'created' => new \DateTimeImmutable('2022-09-10 00:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $rentedCourses[1]->getPrice(),
                'expires' => new \DateTimeImmutable('2023-04-01 00:00:00'),
                'course' => $rentedCourses[1],
                'customer' => $admin,
                'created' => new \DateTimeImmutable('2023-03-20 00:00:00'),
            ],
            // rent
            [
                'type' => 1,
                'amount' => $rentedCourses[0]->getPrice(),
                'expires' => new \DateTimeImmutable('2023-04-02 00:00:00'),
                'course' => $rentedCourses[0],
                'customer' => $user,
                'created' => new \DateTimeImmutable('2023-03-24 00:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $rentedCourses[1]->getPrice(),
                'expires' => new \DateTimeImmutable('2023-03-29 00:00:00'),
                'course' => $rentedCourses[1],
                'customer' => $admin,
                'created' => new \DateTimeImmutable('2023-03-28 00:00:00'),
            ],
        ];

        foreach ($transactions as $transaction) {
            $createdTransaction = new Transaction();
            $createdTransaction
                ->setType($transaction['type'])
                ->setCustomer($transaction['customer'])
                ->setCreated($transaction['created'])
                ->setAmount($transaction['amount']);
            if (isset($transaction['expires'])) {
                $createdTransaction->setExpires($transaction['expires']);
            }
            if (isset($transaction['course'])) {
                $createdTransaction->setCourse($transaction['course']);
            }
            $manager->persist($createdTransaction);
        }

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 2; // smaller means sooner
    }
}