<?php

namespace App\AccessRequest\Presentation\View;

final readonly class AccessRequestView
{
    public function __construct(
        public string $id,
        public string $email,
        public string $companyName,
        public string $reason,
        public ?string $firstName,
        public ?string $lastName,
        public string $createdAt,
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
            'companyName' => $this->companyName,
            'reason' => $this->reason,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'createdAt' => $this->createdAt,
        ];
    }
}