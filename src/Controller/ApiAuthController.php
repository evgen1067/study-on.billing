<?php

namespace App\Controller;

use App\Dto\UserDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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

    #[Route('/auth', name: 'api_auth', methods: ['POST'])]
    public function auth(): JsonResponse
    {
        // get jwt token
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $JWTTokenManager,
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
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($userRepository->findOneBy(['email' => $userDto->getUsername()])) {
            return $this->json([
                'error' => 'Email is already in use.',
            ], Response::HTTP_FORBIDDEN);
        }

        $user = User::fromDTO($userDto);
        $user->setPassword($this->userPasswordHasher->hashPassword(
            $user,
            $user->getPassword()
        ));
        $entityManager->persist($user);
        $entityManager->flush();

        $data = [
            'token' => $JWTTokenManager->create($user),
            'roles' => $user->getRoles(),
        ];

        return $this->json([
            $data
        ], Response::HTTP_CREATED);
    }
}
