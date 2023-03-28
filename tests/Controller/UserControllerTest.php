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
use JsonException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends AbstractTest
{
    private null|Serializer $serializer;

    private string $authApiUrl = '/api/v1/auth';

    private string $registerApiUrl = '/api/v1/register';

    private string $currentApiUrl = '/api/v1/users/current';

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

    /**
     * @throws JsonException
     */
    public function testAuthorizationWithValidCredentials(): void
    {
        $user = [
            'username' => 'user@study-on.ru',
            'password' => 'password',
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->authApiUrl,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertNotEmpty($data['token']);
        self::assertNotEmpty($data['refresh_token']);
    }

    /**
     * @throws JsonException
     */
    public function testAuthorizationWithInvalidCredentials(): void
    {
        $user = [
            'username' => 'not-valid@study-on.ru',
            'password' => 'password',
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->authApiUrl,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $errors = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('401', $errors['code']);
        self::assertEquals('Invalid credentials.', $errors['message']);
    }

    /**
     * @throws JsonException
     */
    public function testRegisterSuccessful(): void
    {
        $user = [
            'username' => 'test@study-on.ru',
            'password' => 'password',
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->registerApiUrl,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_CREATED, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertNotEmpty($data['token']);
        self::assertNotEmpty($data['refresh_token']);
        self::assertNotEmpty($data['roles']);


        self::assertContains('ROLE_USER', $data['roles']);
    }

    /**
     * @throws JsonException
     */
    public function testRegisterWithTooShortPasswordAndNotValidEmail(): void
    {
        $user = [
            'username' => 'test',
            'password' => 'short',
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->registerApiUrl,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST, $client->getResponse());

        $errors = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertNotEmpty($errors['errors']);
        self::assertNotEmpty($errors['errors']['username']);
        self::assertNotEmpty($errors['errors']['password']);

        self::assertEquals('Email "test" не является валидным.', $errors['errors']['username']);
        self::assertEquals('Пароль должен содержать минимум 6 символов.', $errors['errors']['password']);
    }

    /**
     * @throws JsonException
     */
    public function testRegisterWithBlankValues(): void
    {
        $user = [
            'username' => '',
            'password' => '',
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->registerApiUrl,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_BAD_REQUEST, $client->getResponse());

        $errors = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertNotEmpty($errors['errors']);
        self::assertNotEmpty($errors['errors']['username']);
        self::assertNotEmpty($errors['errors']['password']);

        self::assertEquals('Email не может быть пуст.', $errors['errors']['username']);
        self::assertEquals('Пароль не может быть пуст.', $errors['errors']['password']);
    }

    /**
     * @throws JsonException
     */
    public function testRegisterWithAlreadyUsedEmail(): void
    {
        $user = [
            'username' => 'user@study-on.ru',
            'password' => 'password',
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->registerApiUrl,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        $this->assertResponseCode(Response::HTTP_CONFLICT, $client->getResponse());

        $errors = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertNotEmpty($errors['message']);

        self::assertEquals('Email уже используется.', $errors['message']);
    }

    /**
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

        return json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        )['token'];
    }

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
