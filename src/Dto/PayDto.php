<?php

namespace App\Dto;

use DateTimeImmutable;
use JMS\Serializer\Annotation as Serializer;

class PayDto
{
    #[Serializer\Type("bool")]
    private ?bool $success;

    #[Serializer\Type("string")]
    private ?string $type;

    #[Serializer\Type("DateTimeImmutable")]
    private ?DateTimeImmutable $expires;

    public function __construct($success, $type, $expires)
    {
        $this->success = $success;
        $this->type = $type;
        $this->expires = $expires;
    }

    public function getSuccess(): ?bool
    {
        return $this->success;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getExpires(): ?DateTimeImmutable
    {
        return $this->expires;
    }

    public function setSuccess(?bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setExpires(?DateTimeImmutable $expires): self
    {
        $this->expires = $expires;

        return $this;
    }
}
