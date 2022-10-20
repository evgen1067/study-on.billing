<?php

namespace App\Dto;

use App\Entity\Course;
use JMS\Serializer\Annotation as Serializer;

class CourseDto
{
    #[Serializer\Type("string")]
    private string $code;

    #[Serializer\Type("float")]
    private float $price;

    #[Serializer\Type("string")]
    private string $type;

    #[Serializer\Type("string")]
    private string $title;

    public function __construct(Course $course)
    {
        $this->code = $course->getCode();
        $this->price = $course->getPrice();
        $this->type = $course->getType();
        $this->title = $course->getTitle();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}