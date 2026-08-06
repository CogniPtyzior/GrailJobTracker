<?php

namespace App\Security\Domain\Entity;

use App\Security\Domain\ValueObject\UserId;
use App\Security\Domain\ValueObject\UserRoles;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;

final class User
{
    private UserId $id;
    private EmailAddress $email;
    private ?PersonName $firstName = null;
    private ?PersonName $lastName = null;
    private bool $isActive = true;
    private UserRoles $roles;
    private string $passwordHash;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $lastLoginAt = null;

    public function __construct(EmailAddress $email)
    {
        $this->id = UserId::new();
        $this->email = $email;
        $this->roles = UserRoles::regularUser();
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public static function reconstitute(
        UserId $id,
        EmailAddress $email,
        ?PersonName $firstName,
        ?PersonName $lastName,
        bool $isActive,
        UserRoles $roles,
        string $passwordHash,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $lastLoginAt,
    ): self {
        $user = new self($email);
        $user->id = $id;
        $user->updateProfile($firstName, $lastName);
        $user->isActive = $isActive;
        $user->roles = $roles;
        $user->passwordHash = $passwordHash;
        $user->createdAt = $createdAt;
        $user->lastLoginAt = $lastLoginAt;

        return $user;
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email->value();
    }

    public function changeEmail(EmailAddress $email): void
    {
        $this->email = $email;
    }

    public function getNormalizedEmail(): string
    {
        return $this->email->normalizedValue();
    }

    public function firstName(): ?PersonName
    {
        return $this->firstName;
    }


    public function lastName(): ?PersonName
    {
        return $this->lastName;
    }


    public function updateProfile(?PersonName $firstName, ?PersonName $lastName): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function isBootstrapAdmin(string $bootstrapEmail): bool
    {
        return $this->email->equals(EmailAddress::fromString($bootstrapEmail));
    }

    public function roles(): UserRoles
    {
        return $this->roles;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return $this->roles->toArray();
    }

    public function grantAdmin(): void
    {
        $this->roles = UserRoles::admin();
    }

    public function assignRegularUser(): void
    {
        $this->roles = UserRoles::regularUser();
    }

    public function updateAdminRole(bool $isAdmin): void
    {
        if ($isAdmin) {
            $this->grantAdmin();

            return;
        }

        $this->assignRegularUser();
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function markLoggedIn(\DateTimeImmutable $loggedAt): void
    {
        $this->lastLoginAt = $loggedAt;
    }
}
