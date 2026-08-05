<?php

namespace App\Tests\Support\Builder;

use App\Security\Domain\Entity\User;

final class UserBuilder
{
    private string $email = 'john.doe@example.com';
    private ?string $firstName = 'John';
    private ?string $lastName = 'Doe';
    private bool $isActive = true;
    /** @var list<string> */
    private array $roles = ['ROLE_USER'];
    private string $passwordHash = 'hashed-password';
    private ?\DateTimeImmutable $lastLoginAt = null;

    public static function aUser(): self
    {
        return new self();
    }

    public function withEmail(string $email): self
    {
        $clone = clone $this;
        $clone->email = $email;

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

    public function inactive(): self
    {
        $clone = clone $this;
        $clone->isActive = false;

        return $clone;
    }

    /** @param list<string> $roles */
    public function withRoles(array $roles): self
    {
        $clone = clone $this;
        $clone->roles = $roles;

        return $clone;
    }

    public function withPasswordHash(string $passwordHash): self
    {
        $clone = clone $this;
        $clone->passwordHash = $passwordHash;

        return $clone;
    }

    public function loggedInAt(?\DateTimeImmutable $lastLoginAt): self
    {
        $clone = clone $this;
        $clone->lastLoginAt = $lastLoginAt;

        return $clone;
    }

    public function build(): User
    {
        $normalizedEmail = mb_strtolower(trim($this->email));

        $user = new User($this->email, $normalizedEmail);
        $user->updateProfile($this->firstName, $this->lastName);
        $this->isActive ? $user->activate() : $user->deactivate();
        in_array('ROLE_ADMIN', $this->roles, true) ? $user->grantAdmin() : $user->assignRegularUser();
        $user->setPasswordHash($this->passwordHash);

        if ($this->lastLoginAt !== null) {
            $user->markLoggedIn($this->lastLoginAt);
        }

        return $user;
    }
}