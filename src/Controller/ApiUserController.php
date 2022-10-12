<?php

namespace App\Controller;

use App\Repository\UserRepository;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/api/v1/users')]
class ApiUserController extends AbstractController
{
    #[Route('/current', name: 'api_current_user', methods: ['GET'])]
    public function current(UserRepository $userRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'User doesn\'t authorised.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->findOneBy(['email' => $user->getUserIdentifier()]);

        return $this->json([
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'balance' => $user->getBalance(),
        ], Response::HTTP_OK);
    }
}
