<?php

namespace App\Dto;

use DateTimeImmutable;
use JMS\Serializer\Annotation as Serializer;

class TransactionDto
{
    #[Serializer\Type("int")]
    private ?int $id;

    #[Serializer\Type("DateTimeImmutable")]
    private ?DateTimeImmutable $created;

    #[Serializer\Type("string")]
    private ?string $type;

    #[Serializer\Type("string"), Serializer\SkipWhenEmpty]
    private ?string $course_code;

    #[Serializer\Type("float")]
    private ?float $amount;

    #[Serializer\Type("DateTimeImmutable"), Serializer\SkipWhenEmpty]
    private ?DateTimeImmutable $expires;

    public function __construct($id, $created, $type, $courseCode, $amount, $expires)
    {
        $this->id = $id;
        $this->created = $created;
        $this->type = $type;
        $this->amount = $amount;
        $this->expires = $expires ?? null;
        $this->course_code = $courseCode;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
    }

    public function setCreated(?string $created): self
    {
        $this->created = $created;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setCourseCode(?string $courseCode): self
    {
        $this->course_code = $courseCode;

        return $this;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function setExpires(?string $expires): self
    {
        $this->expires = $expires;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getCourseCode(): ?string
    {
        return $this->course_code;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getExpires(): ?DateTimeImmutable
    {
        return $this->expires;
    }
}
