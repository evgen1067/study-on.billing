<?php

namespace App\Tests\Api;

use App\DataFixtures\UserFixtures;
use App\Tests\AbstractTest;
use JMS\Serializer\Serializer;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthTest extends AbstractTest
{
    private null|Serializer $serializer;

    private string $authApiUrl = '/api/v1/auth';

    private string $registerApiUrl = '/api/v1/register';

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    protected function getFixtures(): array
    {
        return [UserFixtures::class];
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

        self::assertEquals('The email "test" is not a valid email.', $errors['errors']['username']);
        self::assertEquals('The password must be at least 6 characters.', $errors['errors']['password']);
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

        self::assertEquals('The username field can\'t be blank.', $errors['errors']['username']);
        self::assertEquals('The password field can\'t be blank.', $errors['errors']['password']);
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

        $this->assertResponseCode(Response::HTTP_FORBIDDEN, $client->getResponse());

        $errors = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertNotEmpty($errors['error']);

        self::assertEquals('Email is already in use.', $errors['error']);
    }

}