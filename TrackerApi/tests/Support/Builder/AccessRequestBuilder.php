<?php

namespace App\Tests\Support\Builder;

use App\AccessRequest\Domain\Entity\AccessRequest;

final class AccessRequestBuilder
{
    private string $email = 'john.doe@example.com';
    private string $companyName = 'Acme';
    private string $reason = 'Need access to manage tracked jobs.';
    private ?string $firstName = 'John';
    private ?string $lastName = 'Doe';

    public static function anAccessRequest(): self
    {
        return new self();
    }

    public function withEmail(string $email): self
    {
        $clone = clone $this;
        $clone->email = $email;

        return $clone;
    }

    public function withCompanyName(string $companyName): self
    {
        $clone = clone $this;
        $clone->companyName = $companyName;

        return $clone;
    }

    public function withReason(string $reason): self
    {
        $clone = clone $this;
        $clone->reason = $reason;

        return $clone;
    }

    public function withFirstName(?string $firstName): self
    {
        $clone = clone $this;
        $clone->firstName = $firstName;

        return $clone;
    }

    public function withLastName(?string $lastName): self
    {
        $clone = clone $this;
        $clone->lastName = $lastName;

        return $clone;
    }

    public function build(): AccessRequest
    {
        $accessRequest = new AccessRequest(
            $this->email,
            mb_strtolower(trim($this->email)),
            $this->companyName,
            $this->reason,
        );
        $accessRequest->setFirstName($this->firstName);
        $accessRequest->setLastName($this->lastName);

        return $accessRequest;
    }
}