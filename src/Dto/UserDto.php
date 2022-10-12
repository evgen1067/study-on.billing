<?php

namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

class UserDto
{
    #[Assert\Email(message: 'The email {{ value }} is not a valid email.')]
    #[Assert\NotBlank(message: 'The username field can\'t be blank.')]
    public ?string $username = null;

    #[Assert\NotBlank(message: 'The password field can\'t be blank')]
    #[Assert\Length(min: 6, minMessage: 'The password must be at least {{ limit }} characters.')]
    public ?string $password= null;

    #[Serializer\Type('float')]
    public float $balance = 5000;

    #
    #[Serializer\Type('array')]
    public array $roles = [];

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

}