<?php

namespace App\Controller;

use App\DTO\CourseDTO;
use App\DTO\PayResponseDTO;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use PHPUnit\Util\Json;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/courses')]
class CourseController extends AbstractController
{
    #[Route('', name: 'app_course_courses', methods: ['GET'])]
    /**
     * @OA\Get(
     *     path="/api/v1/courses",
     *     summary="Список курсов",
     *     description="Список курсов",
     * )
     * @OA\Response(
     *     response=200,
     *     description="Возвращает список курсов",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(
     *           @OA\Property(
     *              property="code",
     *              type="string",
     *              example="CODE"
     *           ),
     *           @OA\Property(
     *              property="type",
     *              type="string",
     *              example="rent"
     *           ),
     *           @OA\Property(
     *              property="price",
     *              type="number",
     *              format="float",
     *              example="2000"
     *           ),
     *           @OA\Property(
     *              property="title",
     *              type="string",
     *              example="Введение в GIT"
     *           ),
     *        )
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
     * @OA\Tag(name="Курс")
     */
    public function courses(
        CourseRepository $repo,
        SerializerInterface $serializer
    ): JsonResponse {
        $courses = $repo->findAll();
        $content = [];
        foreach ($courses as $course) {
            $content[] = new CourseDTO($course);
        }
        return new JsonResponse(
            $serializer->serialize($content, 'json'),
            Response::HTTP_OK
        );
    }

    #[Route('', name: 'app_course_new', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/v1/courses",
     *     summary="Создание нового курса",
     *     description="Создание нового курса"
     * )
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="type",
     *          type="string",
     *          description="Тип курса",
     *          example="free",
     *        ),
     *        @OA\Property(
     *          property="title",
     *          type="string",
     *          description="Название курса",
     *          example="API Course",
     *        ),
     *        @OA\Property(
     *          property="code",
     *          type="string",
     *          description="Код курса",
     *          example="API-1",
     *        ),
     *        @OA\Property(
     *          property="price",
     *          type="number",
     *          format="float",
     *          description="Стоимость курса",
     *          example="0",
     *        ),
     *     )
     *  )
     * @OA\Response(
     *     response=201,
     *     description="Курс успешно создан.",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="success",
     *          type="bool",
     *          example="true",
     *        ),
     *     ),
     * )
     * @OA\Response(
     *     response=409,
     *     description="Курс с таким кодом уже существует.",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="success",
     *          type="bool",
     *          example="false",
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="Курс с таким кодом уже существует.",
     *        ),
     *     ),
     * )
     * @OA\Response(
     *     response=400,
     *     description="Внутренняя ошибка",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="success",
     *          type="bool",
     *          example="false",
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="Внутренняя ошибка на сервисе. Попробуйте позже.",
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
     * @OA\Tag(name="Курс")
     * @Security(name="Bearer")
     */
    public function new(
        Request $request,
        SerializerInterface $serializer,
        CourseRepository $repo,
    ): JsonResponse {
        $dto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');
        $courseDB = $repo->findOneBy(['code' => $dto->getCode()]);

        /**
         * @var User $user
         */
        $user = $this->getUser();
        if (is_null($user)) {
            return new JsonResponse(
                $serializer->serialize(
                    [
                        'code' => Response::HTTP_UNAUTHORIZED,
                        'message' => 'Вы не авторизованы!',
                    ],
                    'json'
                ),
                Response::HTTP_UNAUTHORIZED
            );
        }

        if (!is_null($courseDB)) {
            return new JsonResponse($serializer->serialize([
                'code' => Response::HTTP_CONFLICT,
                'success' => false,
                'message' => 'Курс с таким кодом уже существует.'
            ], 'json'), Response::HTTP_CONFLICT);
        }
        $course = Course::fromDTO($dto);
        $repo->save($course, true);
        return new JsonResponse($serializer->serialize([
            'success' => true,
        ], 'json'), Response::HTTP_CREATED);
    }

    #[Route('/{code}', name: 'app_course_course', methods: ['GET'])]
    /**
     * @OA\Get(
     *     path="/api/v1/courses/{code}",
     *     summary="Получение отдельного курса",
     *     description="Получение отдельного курса",
     * )
     * @OA\Response(
     *     response=200,
     *     description="Возвращает информацию по отдельному курсу",
     *     @OA\JsonContent(
     *         @OA\Property(
     *              property="code",
     *              type="string",
     *              example="CODE"
     *           ),
     *           @OA\Property(
     *              property="type",
     *              type="string",
     *              example="rent"
     *           ),
     *           @OA\Property(
     *              property="price",
     *              type="number",
     *              format="float",
     *              example="2000"
     *           ),
     *           @OA\Property(
     *              property="title",
     *              type="string",
     *              example="Введение в GIT"
     *           ),
     *     )
     * )
     * @OA\Response(
     *     response="404",
     *     description="Курс не найден",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string",
     *          example="404"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="Курс с кодом «PHP-100» не найден"
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
     * @OA\Tag(name="Курс")
     */
    public function course(
        string $code,
        CourseRepository $repo,
        SerializerInterface $serializer
    ): JsonResponse {
        $course = $repo->findOneBy(['code' => $code]);
        if (is_null($course)) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'Курс с кодом «' . $code . '» не найден.',
            ], Response::HTTP_NOT_FOUND);
        }
        return new JsonResponse(
            $serializer->serialize((new CourseDTO($course)), 'json'),
            Response::HTTP_OK
        );
    }

    #[Route('/{code}/pay', name: 'app_course_pay', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/v1/courses/{code}/pay",
     *     summary="Покупка курса",
     *     description="Покупка курса",
     * )
     * @OA\Response(
     *     response=200,
     *     description="Возвращает информацию по отдельному курсу",
     *     @OA\JsonContent(
     *         @OA\Property(
     *              property="success",
     *              type="bool",
     *              example="true"
     *           ),
     *           @OA\Property(
     *              property="type",
     *              type="string",
     *              example="rent"
     *           ),
     *           @OA\Property(
     *              property="expires",
     *              type="string",
     *              example="2022-10-15 22:22:47"
     *           ),
     *     )
     * )
     * @OA\Response(
     *     response="404",
     *     description="Курс не найден",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string",
     *          example="404"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="Курс с кодом «PHP-100» не найден"
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response="406",
     *     description="Недостаточно средств",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string",
     *          example="406"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="На счету недостаточно средств."
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response="401",
     *     description="Пользователь не авторизован",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string",
     *          example="401"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="Вы не авторизованы!"
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
     * @OA\Tag(name="Курс")
     * @Security(name="Bearer")
     */
    public function pay(
        string $code,
        CourseRepository $repo,
        PaymentService $paymentService,
        SerializerInterface $serializer
    ): JsonResponse {
        $course = $repo->findOneBy(['code' => $code]);
        if (is_null($course)) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'Курс с кодом «' . $code . '» не найден.',
            ], Response::HTTP_NOT_FOUND);
        }

        /**
         * @var User $user
         */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(
                $serializer->serialize(
                    [
                        'code' => Response::HTTP_UNAUTHORIZED,
                        'message' => 'Вы не авторизованы!',
                    ],
                    'json'
                ),
                Response::HTTP_UNAUTHORIZED
            );
        }

        try {
            $transaction = $paymentService->payment($user, $course);
            $expires = $transaction->getExpires();
            return new JsonResponse(
                $serializer->serialize(
                    new PayResponseDTO(true, $course->getType(), $expires ?: null),
                    'json'
                ),
                Response::HTTP_OK
            );
        } catch (\Exception $exception) {
            return new JsonResponse(
                $serializer->serialize(
                    [
                        'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => $exception->getMessage()
                    ],
                    'json'
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{code}/edit', name: 'app_course_edit', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/v1/courses/{code}/edit",
     *     summary="Редактирование курса",
     *     description="Редактирование курса"
     * )
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="type",
     *          type="string",
     *          description="Тип курса",
     *          example="free",
     *        ),
     *        @OA\Property(
     *          property="title",
     *          type="string",
     *          description="Название курса",
     *          example="API Course",
     *        ),
     *        @OA\Property(
     *          property="code",
     *          type="string",
     *          description="Код курса",
     *          example="API-1",
     *        ),
     *        @OA\Property(
     *          property="price",
     *          type="number",
     *          format="float",
     *          description="Стоимость курса",
     *          example="0",
     *        ),
     *     )
     *  )
     * @OA\Response(
     *     response=200,
     *     description="Курс успешно изменен.",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="success",
     *          type="bool",
     *          example="true",
     *        ),
     *     ),
     * )
     * @OA\Response(
     *     response="404",
     *     description="Курс не найден",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="code",
     *          type="string",
     *          example="404"
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="Курс с кодом «PHP-100» не найден"
     *        ),
     *     )
     * )
     * @OA\Response(
     *     response=409,
     *     description="Курс с таким кодом уже существует.",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="success",
     *          type="bool",
     *          example="false",
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="Курс с таким кодом уже существует.",
     *        ),
     *     ),
     * )
     * @OA\Response(
     *     response=400,
     *     description="Внутренняя ошибка",
     *     @OA\JsonContent(
     *        @OA\Property(
     *          property="success",
     *          type="bool",
     *          example="false",
     *        ),
     *        @OA\Property(
     *          property="message",
     *          type="string",
     *          example="Внутренняя ошибка на сервисе. Попробуйте позже.",
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
     * @OA\Tag(name="Курс")
     * @Security(name="Bearer")
     */
    public function edit(
        string $code,
        Request $request,
        SerializerInterface $serializer,
        CourseRepository $repo,
    ): JsonResponse {
        $courseDB = $repo->findOneBy(['code' => $code]);

        /**
         * @var User $user
         */
        $user = $this->getUser();
        if (is_null($user)) {
            return new JsonResponse(
                $serializer->serialize(
                    [
                        'code' => Response::HTTP_UNAUTHORIZED,
                        'message' => 'Вы не авторизованы!',
                    ],
                    'json'
                ),
                Response::HTTP_UNAUTHORIZED
            );
        }

        if (is_null($courseDB)) {
            return new JsonResponse([
                'code' => Response::HTTP_NOT_FOUND,
                'success' => false,
                'message' => 'Курс с кодом «' . $code . '» не найден.',
            ], Response::HTTP_NOT_FOUND);
        }

        $dto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');

        if ($dto->code !== $code) {
            $courseDTOCodeDB = $repo->findOneBy(['code' => $dto->code]);
            if (!is_null($courseDTOCodeDB)) {
                return new JsonResponse($serializer->serialize([
                    'code' => Response::HTTP_CONFLICT,
                    'success' => false,
                    'message' => 'Курс с таким кодом уже существует.'
                ], 'json'), Response::HTTP_CONFLICT);
            }
        }

        $courseDB->updateFromDTO($dto);
        $repo->save($courseDB, true);

        return new JsonResponse($serializer->serialize([
            'success' => true,
        ], 'json'), Response::HTTP_OK);
    }
}
