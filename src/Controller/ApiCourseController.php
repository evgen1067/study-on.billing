<?php

namespace App\Controller;

use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use App\Dto\CourseDto;
use App\Dto\PayDto;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('api/v1/courses')]
class ApiCourseController extends AbstractController
{
    #[Route('', name: 'app_courses', methods: ['GET'])]
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
    public function index(CourseRepository $courseRepository, SerializerInterface $serializer): JsonResponse
    {
        $courses = $courseRepository->findAll();
        $coursesDto = [];
        foreach ($courses as $course) {
            $dto = new CourseDto($course);
            $coursesDto[] = $dto;
        }
        $coursesDto = $serializer->serialize($coursesDto, 'json');

        $response = new JsonResponse();
        $response->setContent($coursesDto);
        $response->setStatusCode(Response::HTTP_OK);
        return $response;
    }

    #[Route('/{code}', name: 'app_course', methods: ['GET'])]
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
    public function show(
        string $code,
        CourseRepository $courseRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $course = $courseRepository->findOneBy(['code' => $code]);

        $response = new JsonResponse();
        $content = [
            'code' => Response::HTTP_NOT_FOUND,
            'message' => 'Курс с кодом «' . $code . '» не найден.',
        ];
        if (!is_null($course)) {
            $response->setStatusCode(Response::HTTP_OK);
            $dto = new CourseDto($course);
            $content = $serializer->serialize($dto, 'json');
        } else {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $content = $serializer->serialize($content, 'json');
        }
        $response->setContent($content);
        return $response;
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
        CourseRepository $courseRepository,
        PaymentService $paymentService,
        SerializerInterface $serializer
    ): JsonResponse {
        $course = $courseRepository->findOneBy(['code' => $code]);

        $response = new JsonResponse();

        $content = [
            'code' => Response::HTTP_NOT_FOUND,
            'message' => 'Курс с кодом «' . $code . '» не найден.',
        ];
        if (is_null($course)) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        }
        /**
         * @var User $user
         */
        $user = $this->getUser();

        if (!$user) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $content = [
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Вы не авторизованы!',
            ];
        } else {
            try {
                $transaction = $paymentService->payment($user, $course);
                $expires = $transaction->getExpires();
                $response->setStatusCode(Response::HTTP_OK);
                $content = new PayDto(true, $course->getType(), $expires ?: null);
            } catch (\Exception $exception) {
                $response->setStatusCode($exception->getCode());
                $content = [
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                ];
            }
        }
        $content = $serializer->serialize($content, 'json');
        $response->setContent($content);
        return $response;
    }

    #[Route('/new', name: 'app_course_new', methods: ['POST'])]
    /**
     * @OA\Post(
     *     path="/api/v1/courses/new",
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
        CourseRepository $courseRepository,
    ): JsonResponse {
        $response = new JsonResponse();
        try {
            /**
             * @var CourseDto $courseDto
             */
            $courseDto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');
            $course = $courseRepository->findOneBy(['code' => $courseDto->getCode()]);
            if (!is_null($course)) {
                $response->setStatusCode(Response::HTTP_CONFLICT);
                $content = [
                    'success' => false,
                    'message' => 'Курс с таким кодом уже существует.'
                ];
                $content = $serializer->serialize($content, 'json');
                $response->setContent($content);
                return $response;
            }

            $course = Course::fromDto($courseDto);
            $courseRepository->save($course, true);
            $response->setStatusCode(Response::HTTP_CREATED);
            $content = [
                'success' => true,
            ];
            $content = $serializer->serialize($content, 'json');
            $response->setContent($content);
            return $response;
        } catch (\Exception) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $content = [
                'success' => false,
                'message' => 'Внутренняя ошибка на сервисе. Попробуйте позже.'
            ];
            $content = $serializer->serialize($content, 'json');
            $response->setContent($content);
            return $response;
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
        CourseRepository $courseRepository,
    ): JsonResponse {
        $response = new JsonResponse();

        try {
            $courseBeforeUpdate = $courseRepository->findOneBy(['code' => $code]);
            /**
             * @var CourseDto $courseDto
             */
            $courseDto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');
            if ($courseBeforeUpdate !== null) {
                $courseWithNewCode = $courseRepository->findOneBy(['code' =>  $courseDto->getCode()]);
                if ($courseWithNewCode !== null && $courseDto->getCode() !== $code) {
                    $response->setStatusCode(Response::HTTP_CONFLICT);
                    $content = [
                        'success' => false,
                        'message' => 'Курс с кодом «' . $courseDto->getCode() . '» уже существует.',
                    ];
                    $content = $serializer->serialize($content, 'json');
                    $response->setContent($content);
                    return $response;
                }
                $courseBeforeUpdate->updateFromDto($courseDto);
                $courseRepository->save($courseBeforeUpdate, true);
                $response->setStatusCode(Response::HTTP_OK);
                $content = [
                    'success' => true,
                ];
                $content = $serializer->serialize($content, 'json');
                $response->setContent($content);
                return $response;
            }
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $content = [
                'success' => false,
                'message' => 'Курс с кодом «' . $code . '» не найден.',
            ];
            $content = $serializer->serialize($content, 'json');
            $response->setContent($content);
            return $response;
        } catch (\Exception $exception) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $content = [
                'success' => false,
                'message' => 'Внутренняя ошибка на сервисе. Попробуйте позже.'
            ];
            $content = $serializer->serialize($content, 'json');
            $response->setContent($content);
            return $response;
        }
    }
}
