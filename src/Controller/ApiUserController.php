<?php

namespace App\Controller;

use App\Repository\UserRepository;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

#[Route('/api/v1/users')]
class ApiUserController extends AbstractController
{
    #[Route('/current', name: 'api_current_user', methods: ['GET'])]
    /**
     * @OA\Get(
     *     path="/api/v1/users/current",
     *     summary="Получение текущего пользователя",
     *     description="Получение текущего пользователя"
     * )
     * @OA\Response(
     *     response=200,
     *     description="Возвращает информацию по текущему пользователю",
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
     *     description="Нет текущего авторизованного пользователя",
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
     * @OA\Tag(name="Пользователь")
     * @Security(name="Bearer")
     */
    public function current(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'User doesn\'t authorised.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->findOneBy(['email' => $user->getUserIdentifier()]);

        $data = [
            'username' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'balance' => $user->getBalance(),
        ];
        $response = new JsonResponse();
        $data = $serializer->serialize($data, 'json');
        $response->setContent($data);
        $response->setStatusCode(Response::HTTP_OK);
        return $response;
    }
}
