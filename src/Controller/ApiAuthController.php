<?php

namespace App\Controller;

use App\Dto\UserDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1')]
class ApiAuthController extends AbstractController
{
    private ValidatorInterface $validator;

    private Serializer $serializer;

    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(
        ValidatorInterface $validator,
        UserPasswordHasherInterface $userPasswordHasher
    )
    {
        $this->validator = $validator;
        $this->serializer = SerializerBuilder::create()->build();
        $this->userPasswordHasher = $userPasswordHasher;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth",
     *     summary="Authorization of a user with a jwt token",
     *     description="Authorization of a user with a jwt token"
     * )
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="username",
     *          type="string",
     *          description="User name",
     *          example="user@study-on.ru",
     *        ),
     *        @OA\Property(
     *          property="password",
     *          type="string",
     *          description="Password",
     *          example="password",
     *        ),
     *     )
     *  )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns JWT token",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="token",
     *          type="string",
     *        ),
     *        @OA\Property(
     *          property="refresh_token",
     *          type="string",
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Authorization error",
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
     *     description="Unexpected error",
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
    #[Route('/auth', name: 'api_auth', methods: ['POST'])]
    public function auth(): JsonResponse
    {
        // get jwt token
    }

    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     summary="User Registration",
     *     description="User Registration"
     * )
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="username",
     *          type="string",
     *          description="User name",
     *          example="user@study-on.ru",
     *        ),
     *        @OA\Property(
     *          property="password",
     *          type="string",
     *          description="Password",
     *          example="password",
     *        ),
     *     )
     *  )
     * )
     * @OA\Response(
     *     response=201,
     *     description="Successful registration",
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
     *     description="Validation error",
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
     *     response=403,
     *     description="Email is already in use",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string",
     *          example="403",
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="Email is already in use.",
     *        ),
     *     ),
     * )
     * @OA\Response(
     *     response="default",
     *     description="Unexpected error",
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
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $JWTTokenManager,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager
    ): JsonResponse
    {
        $userDto = $this->serializer->deserialize($request->getContent(), UserDto::class, 'json');

        $errors = $this->validator->validate($userDto);

        if (count($errors) > 0) {
            $jsonErrors = [];
            foreach ($errors as $error) {
                $jsonErrors[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json([
                'errors' => $jsonErrors,
                'code' => Response::HTTP_BAD_REQUEST,
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($userRepository->findOneBy(['email' => $userDto->getUsername()])) {
            return $this->json([
                'message' => 'Email уже используется.',
                'code' => Response::HTTP_FORBIDDEN
            ], Response::HTTP_FORBIDDEN);
        }

        $user = User::fromDTO($userDto);
        $user->setPassword($this->userPasswordHasher->hashPassword(
            $user,
            $user->getPassword()
        ));
        $entityManager->persist($user);
        $entityManager->flush();

        $refreshToken = $refreshTokenGenerator->createForUserWithTtl(
            $user,
            (new \DateTime())->modify('+1 month')->getTimestamp()
        );
        $refreshTokenManager->save($refreshToken);

        $data = [
            'token' => $JWTTokenManager->create($user),
            'refresh_token' => $refreshToken->getRefreshToken(),
            'roles' => $user->getRoles(),
        ];

        return $this->json($data,Response::HTTP_CREATED);
    }
}
