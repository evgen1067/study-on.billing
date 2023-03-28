<?php

namespace App\DTO;

use App\Entity\Course;
use JMS\Serializer\Annotation as Serializer;

class CourseDTO
{
    #[Serializer\Type("string")]
    public string $code;

    #[Serializer\Type("float")]
    public float $price;

    #[Serializer\Type("string")]
    public string $type;

    #[Serializer\Type("string")]
    public string $title;

    public function __construct(Course|null $course)
    {
        if (!is_null($course)) {
            $this->code = $course->getCode();
            $this->price = $course->getPrice();
            $this->type = $course->getType();
            $this->title = $course->getTitle();
        }
    }

}