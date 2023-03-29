<?php

namespace App\DTO\Response;

use App\Entity\Transaction;
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

    public function __construct(Transaction $t)
    {
        $this->id = $t->getId();
        $this->created = $t->getCreated();
        $this->type = $t->getType();
        $this->amount = $t->getAmount();
        $this->expires = $t->getExpires();
        $this->course_code = $t->getCourse()?->getCode();
    }
}
