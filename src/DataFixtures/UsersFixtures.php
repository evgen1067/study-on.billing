<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHashed;

    public function __construct(
        UserPasswordHasherInterface $userPasswordHashed,
    ) {
        $this->userPasswordHashed = $userPasswordHashed;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $password = $this->userPasswordHashed->hashPassword(
            $user,
            'password'
        );
        $user
            ->setEmail('user@study-on.ru')
            ->setPassword($password)
            ->setBalance($_ENV['BALANCE_START']);

        $manager->persist($user);

        $admin = new User();
        $password = $this->userPasswordHashed->hashPassword(
            $admin,
            'password'
        );
        $admin
            ->setEmail('admin@study-on.ru')
            ->setPassword($password)
            ->setRoles(['ROLE_SUPER_ADMIN'])
            ->setBalance($_ENV['BALANCE_START']);

        $manager->persist($admin);

        $manager->flush();
    }
}