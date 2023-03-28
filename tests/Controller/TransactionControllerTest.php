<?php

namespace App\Tests\Controller;

use App\DataFixtures\CoursesFixtures;
use App\DataFixtures\TransactionsFixtures;
use App\DataFixtures\UsersFixtures;
use App\Service\PaymentService;
use App\Tests\AbstractTest;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TransactionControllerTest extends AbstractTest
{
    private null|Serializer $serializer;

    private string $authApiUrl = '/api/v1/auth';

    private string $transactionsApiUrl = '/api/v1/transactions';

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    protected function getFixtures(): array
    {
        return [
            new UsersFixtures(
                self::getContainer()->get(UserPasswordHasherInterface::class),
                self::getContainer()->get(RefreshTokenGeneratorInterface::class),
                self::getContainer()->get(RefreshTokenManagerInterface::class),
                self::getContainer()->get(PaymentService::class),
            ),
            new CoursesFixtures(),
            new TransactionsFixtures(),
        ];
    }


    private function getToken($user): mixed
    {
        $client = self::getClient();
        $client->request(
            'POST',
            $this->authApiUrl,
            [],
            [],
            [ 'CONTENT_TYPE' => 'application/json' ],
            $this->serializer->serialize($user, 'json')
        );

        return json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)['token'];
    }

    public function testGetTransactionsUnauthorized(): void
    {
        $token = 'invalid-token';

        $client = self::getClient();
        $client->request(
            'GET',
            $this->transactionsApiUrl,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());
    }

    public function testGetTransactionsWithoutFiltersSuccessful(): void
    {
        $user = [
            'username' => 'admin@study-on.ru',
            'password' => 'password',
        ];

        $token = $this->getToken($user);

        $client = self::getClient();
        $client->request(
            'GET',
            $this->transactionsApiUrl,
            [],
            [],
            [
                'accept' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertCount(6, $response);
    }
    public function testGetTransactionsWithFiltersSuccessful(): void
    {
        $user = [
            'username' => 'user@study-on.ru',
            'password' => 'password',
        ];

        $token = $this->getToken($user);

        $filters = [
            'type' => 'payment',
            'course_code' => 'PHP-1',
            'skip_expired' => true,
        ];

        $client = self::getClient();
        $client->request(
            'GET',
            $this->transactionsApiUrl,
            $filters,
            [],
            [
                'accept' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertCount(2, $response);
    }

}