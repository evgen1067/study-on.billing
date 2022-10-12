<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $password = $this->userPasswordHasher->hashPassword(
            $user,
            'password'
        );
        $user
            ->setEmail('user@study-on.ru')
            ->setPassword($password)
            ->setBalance(5000.0);

        $admin = new User();
        $password = $this->userPasswordHasher->hashPassword(
            $admin,
            'password'
        );
        $admin
            ->setEmail('admin@study-on.ru')
            ->setPassword($password)
            ->setRoles(['ROLE_SUPER_ADMIN'])
            ->setBalance(100000.0);

        $manager->persist($user);
        $manager->persist($admin);

        $manager->flush();
    }
}
