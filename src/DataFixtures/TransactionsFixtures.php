<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use DateInterval;
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
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P3Y')),
            ],
            [
                'type' => 2,
                'amount' => 2000,
                'customer' => $admin,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P3Y')),
            ],
            // buy
            [
                'type' => 1,
                'amount' => $boughtCourses[0]->getPrice(),
                'course' => $boughtCourses[0],
                'customer' => $user,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P1Y3M6D')),
            ],
            [
                'type' => 1,
                'amount' => $boughtCourses[1]->getPrice(),
                'course' => $boughtCourses[1],
                'customer' => $admin,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P1Y1M2D')),
            ],
            // rent - expires
            [
                'type' => 1,
                'amount' => $rentedCourses[0]->getPrice(),
                'expires' => (new DateTimeImmutable())->sub(new DateInterval('P1Y3M6D')),
                'course' => $rentedCourses[0],
                'customer' => $user,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P1Y3M13D')),
            ],
            [
                'type' => 1,
                'amount' => $rentedCourses[0]->getPrice(),
                'expires' => (new DateTimeImmutable())->sub(new DateInterval('P2Y3M6D')),
                'course' => $rentedCourses[0],
                'customer' => $admin,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P2Y3M13D')),
            ],
            [
                'type' => 1,
                'amount' => $rentedCourses[1]->getPrice(),
                'expires' => (new DateTimeImmutable())->sub(new DateInterval('P2Y3M6D')),
                'course' => $rentedCourses[1],
                'customer' => $user,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P2Y3M13D')),
            ],
            [
                'type' => 1,
                'amount' => $rentedCourses[1]->getPrice(),
                'expires' => (new DateTimeImmutable())->sub(new DateInterval('P1Y3M6D')),
                'course' => $rentedCourses[1],
                'customer' => $admin,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P1Y3M13D')),
            ],
            // rent
            [
                'type' => 1,
                'amount' => $rentedCourses[0]->getPrice(),
                'expires' => (new DateTimeImmutable())->add(new DateInterval('P15D')),
                'course' => $rentedCourses[0],
                'customer' => $user,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P12D')),
            ],
            [
                'type' => 1,
                'amount' => $rentedCourses[1]->getPrice(),
                'expires' => (new DateTimeImmutable())->add(new DateInterval('P16D')),
                'course' => $rentedCourses[1],
                'customer' => $admin,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P12D')),
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