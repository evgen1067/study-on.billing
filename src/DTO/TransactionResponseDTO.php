<?php

namespace App\DTO;

use DateTimeImmutable;
use JMS\Serializer\Annotation as Serializer;

class TransactionResponseDTO
{
    #[Serializer\Type("int")]
    public ?int $id;

    #[Serializer\Type("DateTimeImmutable")]
    public ?DateTimeImmutable $created;

    #[Serializer\Type("string")]
    public ?string $type;

    #[Serializer\Type("string"), Serializer\SkipWhenEmpty]
    public ?string $course_code;

    #[Serializer\Type("float")]
    public ?float $amount;

    #[Serializer\Type("DateTimeImmutable"), Serializer\SkipWhenEmpty]
    public ?DateTimeImmutable $expires;

    public function __construct($id, $created, $type, $courseCode, $amount, $expires)
    {
        $this->id = $id;
        $this->created = $created;
        $this->type = $type;
        $this->amount = $amount;
        $this->expires = $expires ?? null;
        $this->course_code = $courseCode;
    }
}
