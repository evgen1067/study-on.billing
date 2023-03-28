<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\PaymentService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UsersFixtures extends Fixture implements OrderedFixtureInterface
{
    private UserPasswordHasherInterface $userPasswordHashed;

    private RefreshTokenGeneratorInterface $refreshTokenGenerator;

    private RefreshTokenManagerInterface $refreshTokenManager;

    private PaymentService $paymentService;

    public function __construct(
        UserPasswordHasherInterface $userPasswordHashed,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager,
        PaymentService $paymentService,
    ) {
        $this->userPasswordHashed = $userPasswordHashed;
        $this->refreshTokenGenerator = $refreshTokenGenerator;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->paymentService = $paymentService;
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
            ->setBalance(0.0);

        $manager->persist($user);

        $this->paymentService->deposit($user, $_ENV['BALANCE_START']);

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
            ->setBalance(0.0);

        $manager->persist($admin);

        $this->paymentService->deposit($admin, $_ENV['BALANCE_START']);

        $refreshToken = $this->refreshTokenGenerator
            ->createForUserWithTtl($admin, (new \DateTime())->modify('+1 month')->getTimestamp());
        $this->refreshTokenManager->save($refreshToken);

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 0; // smaller means sooner
    }
}