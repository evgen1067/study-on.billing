<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

class UserRequestDTO
{
    #[Serializer\Type('string')]
    #[Assert\Email(message: 'Email {{ value }} не является валидным.')]
    #[Assert\NotBlank(message: 'Email не может быть пуст.')]
    public ?string $username;

    #[Serializer\Type('string')]
    #[Assert\Length(min: 6, minMessage: 'Пароль должен содержать минимум {{ limit }} символов.')]
    #[Assert\NotBlank(message: 'Пароль не может быть пуст.')]
    public ?string $password;
}