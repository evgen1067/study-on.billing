<?php

namespace App\DTO;

use DateTimeImmutable;
use JMS\Serializer\Annotation as Serializer;

class PayResponseDTO
{
    #[Serializer\Type("bool")]
    public ?bool $success;

    #[Serializer\Type("string")]
    public ?string $type;

    #[Serializer\Type("DateTimeImmutable")]
    public ?DateTimeImmutable $expires;

    public function __construct($success, $type, $expires)
    {
        $this->success = $success;
        $this->type = $type;
        $this->expires = $expires;
    }
}