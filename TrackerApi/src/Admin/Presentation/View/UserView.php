<?php

namespace App\Admin\Presentation\View;

final readonly class UserView
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $id,
        public string $email,
        public ?string $firstName,
        public ?string $lastName,
        public bool $isActive,
        public array $roles,
        public string $createdAt,
        public ?string $lastLoginAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'isActive' => $this->isActive,
            'roles' => $this->roles,
            'createdAt' => $this->createdAt,
            'lastLoginAt' => $this->lastLoginAt,
        ];
    }
}