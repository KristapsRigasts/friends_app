<?php

namespace App\Models;

class User
{
    private string $name;
    private string $surname;
    private ?int $id;

    public function __construct(string $name, string $surname, ?int $id = null)
    {
        $this->name = $name;
        $this->surname = $surname;
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
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