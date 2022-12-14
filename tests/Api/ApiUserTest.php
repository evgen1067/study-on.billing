<?php

namespace App\Tests\Api;

use App\DataFixtures\UserFixtures;
use App\Service\PaymentService;
use App\Tests\AbstractTest;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use JMS\Serializer\Serializer;
use JsonException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApiUserTest extends AbstractTest
{
    private null|Serializer $serializer;

    private string $authApiUrl = '/api/v1/auth';

    private string $currentApiUrl = '/api/v1/users/current';

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    protected function getFixtures(): array
    {
        return [
            new UserFixtures(
                self::getContainer()->get(UserPasswordHasherInterface::class),
                self::getContainer()->get(RefreshTokenGeneratorInterface::class),
                self::getContainer()->get(RefreshTokenManagerInterface::class),
                self::getContainer()->get(PaymentService::class),
            )];
    }

    /**
     * @param $user
     * @return mixed
     * @throws JsonException
     */
    private function getToken($user)
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

    /**
     * @return void
     * @throws JsonException
     */
    public function testGetCurrentUserIsSuccessful(): void
    {
        $user = [
            'username' => 'user@study-on.ru',
            'password' => 'password',
        ];

        $token = $this->getToken($user);

        $client = self::getClient();
        $client->request(
            'GET',
            $this->currentApiUrl,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertNotEmpty($data['username']);
        self::assertNotEmpty($data['roles']);

        self::assertIsString($data['username']);
        self::assertIsArray($data['roles']);
        self::assertIsNumeric($data['balance']);
    }

    /**
     * @return void
     */
    public function testGetCurrentUserIsNotSuccessful(): void
    {
        $token = 'invalid-token';

        $client = self::getClient();
        $client->request(
            'GET',
            $this->currentApiUrl,
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json',
            ],
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());
    }
}
