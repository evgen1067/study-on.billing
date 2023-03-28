<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHashed;

    private RefreshTokenGeneratorInterface $refreshTokenGenerator;

    private RefreshTokenManagerInterface $refreshTokenManager;

    public function __construct(
        UserPasswordHasherInterface $userPasswordHashed,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager,
    ) {
        $this->userPasswordHashed = $userPasswordHashed;
        $this->refreshTokenGenerator = $refreshTokenGenerator;
        $this->refreshTokenManager = $refreshTokenManager;
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

        $refreshToken = $this->refreshTokenGenerator
            ->createForUserWithTtl($user, (new \DateTime())->modify('+1 month')->getTimestamp());
        $this->refreshTokenManager->save($refreshToken);

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

        $refreshToken = $this->refreshTokenGenerator
            ->createForUserWithTtl($admin, (new \DateTime())->modify('+1 month')->getTimestamp());
        $this->refreshTokenManager->save($refreshToken);

        $manager->flush();
    }
}