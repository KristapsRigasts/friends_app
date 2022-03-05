<?php

namespace App\Models;

class User
{
    private string $name;
    private string $surname;
    private ?int $user_id;

    public function __construct(string $name, string $surname, ?int $user_id = null)
    {
        $this->name = $name;
        $this->surname = $surname;
        $this->user_id = $user_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }


}