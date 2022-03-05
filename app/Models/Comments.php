<?php

namespace App\Models;

class Comments
{
    private string $comment;
    private string $created_at;

    public function __construct(string $comment, string $created_at)
    {
        $this->comment = $comment;
        $this->created_at = $created_at;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }
}