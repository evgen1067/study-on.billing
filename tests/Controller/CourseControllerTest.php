<?php

namespace templates;

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

class CourseControllerTest extends AbstractTest
{
    private null|Serializer $serializer;

    private string $authApiUrl = '/api/v1/auth';

    private string $coursesApiUrl = '/api/v1/courses';

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
     * @param $user
     * @return mixed
     * @throws \JsonException
     */
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

    /**
     * @return void
     */
    public function testGetCoursesList(): void
    {
        $client = self::getClient();
        $client->request(
            'GET',
            $this->coursesApiUrl,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');
        self::assertCount(5, $response);
    }

    /**
     * @return void
     */
    public function testGetCoursesWithNonExistentCode(): void
    {
        $nonExistentCode = 'NOT-1';

        $client = self::getClient();
        $client->request(
            'GET',
            $this->coursesApiUrl . '/' . $nonExistentCode,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
        );

        $this->assertResponseCode(Response::HTTP_NOT_FOUND, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertEquals(404, $response['code']);
        self::assertEquals('Курс с кодом «' . $nonExistentCode . '» не найден.', $response['message']);
    }

    public function testGetCoursesIsSuccessful(): void
    {
        $existingCode = 'PHP-1';

        $client = self::getClient();
        $client->request(
            'GET',
            $this->coursesApiUrl . '/' . $existingCode,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertNotEmpty($response['code']);
        self::assertNotEmpty($response['price']);
        self::assertNotEmpty($response['type']);
        self::assertNotEmpty($response['title']);
    }

    public function testCoursePaymentUnauthorized(): void
    {
        $existingCode = 'PHP-1';

        $client = self::getClient();
        $client->request(
            'POST',
            $this->coursesApiUrl . '/' . $existingCode . '/pay',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertNotEmpty($response['code']);
        self::assertNotEmpty($response['message']);

        self::assertEquals(401, $response['code']);
    }

    public function testCoursePaymentAuthorized(): void
    {
        $user = [
            'username' => 'admin@study-on.ru',
            'password' => 'password',
        ];

        $token = $this->getToken($user);

        $existingCode = 'OS-1';

        $client = self::getClient();
        $client->request(
            'POST',
            $this->coursesApiUrl . '/' . $existingCode . '/pay',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertNotEmpty($response['success']);
        self::assertEquals(true, $response['success']);
    }

    public function testCreateCourseIsSuccessful()
    {
        $user = [
            'username' => 'admin@study-on.ru',
            'password' => 'password',
        ];

        $token = $this->getToken($user);
        $data = [
            'type' => 'free',
            'title' => 'API Course',
            'code' => 'API-1',
            'price' => 0,
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->coursesApiUrl,
            $data,
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            $this->serializer->serialize($data, 'json'),
        );

        $this->assertResponseCode(Response::HTTP_CREATED, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertNotEmpty($response['success']);
        self::assertEquals(true, $response['success']);
    }

    public function testCreateCourseWithExistingCode()
    {
        $user = [
            'username' => 'admin@study-on.ru',
            'password' => 'password',
        ];

        $token = $this->getToken($user);

        $data = [
            'type' => 'free',
            'title' => 'API Course',
            'code' => 'PHP-1',
            'price' => 0,
        ];

        $client = self::getClient();
        $client->request(
            'POST',
            $this->coursesApiUrl,
            $data,
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            $this->serializer->serialize($data, 'json'),
        );

        $this->assertResponseCode(Response::HTTP_CONFLICT, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertIsBool($response['success']);
        self::assertEquals(false, $response['success']);
    }

    public function testEditCourseIsSuccessful()
    {
        $user = [
            'username' => 'admin@study-on.ru',
            'password' => 'password',
        ];

        $token = $this->getToken($user);

        $data = [
            'type' => 'free',
            'title' => 'API Course',
            'code' => 'API-1',
            'price' => 0,
        ];

        $courseCode = '/PHP-1';

        $client = self::getClient();
        $client->request(
            'POST',
            $this->coursesApiUrl . $courseCode . '/edit',
            $data,
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            $this->serializer->serialize($data, 'json'),
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertIsBool($response['success']);
        self::assertEquals(true, $response['success']);
    }

    public function testEditCourseWithExistingCode()
    {
        $user = [
            'username' => 'admin@study-on.ru',
            'password' => 'password',
        ];

        $token = $this->getToken($user);

        $data = [
            'type' => 'free',
            'title' => 'API Course',
            'code' => 'JS-1',
            'price' => 0,
        ];

        $courseCode = '/PHP-1';

        $client = self::getClient();
        $client->request(
            'POST',
            $this->coursesApiUrl . $courseCode . '/edit',
            $data,
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            $this->serializer->serialize($data, 'json'),
        );

        $this->assertResponseCode(Response::HTTP_CONFLICT, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertIsBool($response['success']);
        self::assertEquals(false, $response['success']);
    }
}
