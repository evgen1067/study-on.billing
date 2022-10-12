<?php

namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

class UserDto
{
    #[Serializer\Type('string')]
    #[Assert\Email(message: 'The email {{ value }} is not a valid email.')]
    #[Assert\NotBlank(message: 'The username field can\'t be blank.')]
    public ?string $username;

    #[Serializer\Type('string')]
    #[Assert\Length(min: 6, minMessage: 'The password must be at least {{ limit }} characters.')]
    #[Assert\NotBlank(message: 'The password field can\'t be blank.')]
    public ?string $password;

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}