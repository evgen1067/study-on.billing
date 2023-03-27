<?php

namespace App\Controller;

use App\DTO\UserRegisterDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Annotations as OA;

#[Route('/api/v1')]
class UserController extends AbstractController
{
    private ValidatorInterface $validator;

    private Serializer $serializer;

    private UserPasswordHasherInterface $hasher;

    public function __construct(
        ValidatorInterface $validator,
        UserPasswordHasherInterface $hasher
    ) {
        $this->validator = $validator;
        $this->serializer = SerializerBuilder::create()->build();
        $this->hasher = $hasher;
    }


    #[Route('/auth', name: 'api_auth', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/v1/auth",
     *     summary="Аутентификация пользователя и получение JWT-токена",
     *     description="Аутентификация пользователя и получение JWT-токена"
     * )
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="username",
     *          type="string",
     *          description="email пользователя",
     *          example="user@study-on.ru",
     *        ),
     *        @OA\Property(
     *          property="password",
     *          type="string",
     *          description="пароль пользователя",
     *          example="password",
     *        ),
     *     )
     *)
     * @OA\Response(
     *     response=200,
     *     description="Аутентификация пользователя и получение JWT-токена",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="token",
     *          type="string",
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Ошибка аутентификации",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string",
     *          example="401"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="Invalid credentials."
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response="default",
     *     description="Неизвестная ошибка",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     * @OA\Tag(name="User")
     */
    public function auth(): JsonResponse
    {
        // get jwt token
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     summary="Регистрация пользователя и получение JWT-токена",
     *     description="Регистрация пользователя и получение JWT-токена"
     * )
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="username",
     *          type="string",
     *          description="email пользователя",
     *          example="user@study-on.ru",
     *        ),
     *        @OA\Property(
     *          property="password",
     *          type="string",
     *          description="пароль пользователя",
     *          example="password",
     *        ),
     *     )
     *  )
     * )
     * @OA\Response(
     *     response=201,
     *     description="Успешная регистрация",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="token",
     *          type="string",
     *        ),
     *        @OA\Property(
     *          property="roles",
     *          type="array",
     *          @OA\Items(
     *              type="string",
     *          ),
     *        ),
     *     ),
     * )
     * @OA\Response(
     *     response=400,
     *     description="Ошибка валидации",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="errors",
     *          type="array",
     *          @OA\Items(
     *              @OA\Property(
     *                  type="string",
     *                  property="property"
     *              )
     *          )
     *        )
     *     )
     * )
     * @OA\Response(
     *     response=409,
     *     description="Email уже используется.",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="error",
     *          type="string",
     *          example="Email уже используется.",
     *        ),
     *     ),
     * )
     * @OA\Response(
     *     response="default",
     *     description="Неизвестная ошибка",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string",
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *        ),
     *     ),
     * )
     * @OA\Tag(name="User")
     */
    public function register(
        Request $req,
        UserRepository $repo,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $dto = $this->serializer->deserialize($req->getContent(), UserRegisterDTO::class, 'json');
        $errs = $this->validator->validate($dto);

        if (count($errs) > 0) {
            $jsonErrors = [];
            foreach ($errs as $error) {
                $jsonErrors[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['error' => $jsonErrors], Response::HTTP_BAD_REQUEST);
        }

        if ($repo->findOneBy(['email' => $dto->username])) {
            return new JsonResponse(['error' => 'Email уже используется.'], Response::HTTP_CONFLICT);
        }
        $user = User::fromDTO($dto);
        $user->setPassword(
            $this->hasher->hashPassword($user, $user->getPassword())
        );
        $repo->save($user, true);
        return new JsonResponse([
            'token' => $jwtManager->create($user),
            'roles' => $user->getRoles(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/users/current', name: 'api_current_user', methods: ['GET'])]
    /**
     * @OA\Get(
     *     path="/api/v1/users/current",
     *     summary="Получение информации о текущем пользователе",
     *     description="Получение информации о текущем пользователе"
     * )
     * @OA\Response(
     *     response=200,
     *     description="Получение информации о текущем пользователе",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="username",
     *          type="string",
     *        ),
     *        @OA\Property(
     *          property="roles",
     *          type="array",
     *          @OA\Items(
     *              type="string"
     *          )
     *        ),
     *        @OA\Property(
     *          property="balance",
     *          type="number",
     *          format="float"
     *        )
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Пользователь не авторизован",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="error",
     *          type="string"
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response="default",
     *     description="Неизвестная ошибка",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string"
     *        ),
     *     )
     * )
     * @OA\Tag(name="User")
     * @Security(name="Bearer")
     */
    #[Security(name: 'Bearer')]
    public function current(
        Request $req,
        UserRepository $repo,
    ): JsonResponse {
        $token = explode(' ', $req->headers->get('Authorization', ' '));
        if (count($token) <= 1) {
            /**
             * @var $user User
             */
            $user = $this->getUser();

            if (!$user) {
                return new JsonResponse(['error' => 'Пользователь не авторизован.'], Response::HTTP_UNAUTHORIZED);
            }

            return new JsonResponse([
                'username' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'balance' => $user->getBalance(),
            ], Response::HTTP_OK);
        }

        try {
            $payload = json_decode(base64_decode(explode(
                '.',
                $token[1]
            )[1]), true, 512, JSON_THROW_ON_ERROR);

            $user = $repo->findOneBy(['email' => $payload['email']]);
            if ($user) {
                return new JsonResponse([
                    'username' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'balance' => $user->getBalance(),
                ], Response::HTTP_OK);
            }
            return new JsonResponse(['error' => 'Пользователь с таким email не найден.'], Response::HTTP_UNAUTHORIZED);
        } catch (\JsonException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }
}
