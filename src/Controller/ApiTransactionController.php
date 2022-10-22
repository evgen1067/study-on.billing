<?php

namespace App\Controller;

use App\Dto\TransactionDto;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('api/v1/transactions')]
class ApiTransactionController extends AbstractController
{
    private const OPERATION_TYPE = [
        'payment' => 1,
        'deposit' => 2
    ];

    /**
     * @OA\Get(
     *     path="/api/v1/transactions",
     *     summary="Получение истории транзакций",
     *     description="Получение истории транзакций",
     * )
     * @OA\Parameter(
     *     name="type",
     *     description="Тип транзакции (payment, deposit)",
     *     in="query",
     *     example="payment"
     * )
     * @OA\Parameter(
     *     name="course_code",
     *     description="Символьный код курса",
     *     in="query",
     *     example="PHP-1"
     * )
     * @OA\Parameter(
     *     name="skip_expired",
     *     description="Флаг, позволяющий отбросить записи с датой в прошлом (т.е. оплаты аренд, которые уже истекли)",
     *     in="query",
     *     example="true"
     * )
     * @OA\Response(
     *     response=200,
     *     description="Возвращает информацию по текущему пользователю",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(
     *          @OA\Property(
     *            property="id",
     *            type="string",
     *          ),
     *          @OA\Property(
     *            property="created",
     *            type="string",
     *          ),
     *          @OA\Property(
     *            property="type",
     *            type="string",
     *          ),
     *          @OA\Property(
     *            property="courseCode",
     *            type="string",
     *          ),
     *          @OA\Property(
     *            property="amount",
     *            type="string",
     *          ),
     *          @OA\Property(
     *            property="expires",
     *            type="string",
     *          ),
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
     * @OA\Tag(name="Транзакции")
     * @Security(name="Bearer")
     */
    #[Route('', name: 'app_transaction', methods: ['GET'])]
    public function index(
        Request $request,
        TransactionRepository $transactionRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $filters = [];
        # тип транзакции payment|deposit
        $filters['type'] = $request->query->get('type') ? self::OPERATION_TYPE[$request->query->get('type')] : null;
        # символьный код курса
        $filters['course_code'] = $request->query->get('course_code');
        # флаг, позволяющий отбросить записи с датой expires_at в прошлом (т.е. оплаты аренд, которые уже истекли).
        $filters['skip_expired'] = $request->query->get('skip_expired');

        /**
         * @var User $user
         */
        $user = $this->getUser();

        $transactions = $transactionRepository->findUserTransactionsWithFilters($user, $filters);
        $content = [];
        foreach ($transactions as $transaction) {
            /**
             * @var Transaction $transaction
             */
            $transactionDto = new TransactionDto(
                $transaction->getId(),
                $transaction->getCreated(),
                $transaction->getType(),
                $transaction->getCourse()?->getCode(),
                $transaction->getAmount(),
                $transaction->getExpires()
            );
            $content[] = $transactionDto;
        }
        $content = $serializer->serialize($content, 'json');
        $response = new JsonResponse();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($content);
        return $response;
    }
}
