<?php

namespace App\Security\Domain\Entity;

use App\Security\Infrastructure\Doctrine\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users', schema: 'trackers')]
#[ORM\Index(columns: ['normalized_email'], name: 'idx_users_normalized_email')]
#[ORM\Index(columns: ['is_active'], name: 'idx_users_is_active')]
final class User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(name: 'normalized_email', length: 180, unique: true)]
    private string $normalizedEmail;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column]
    private bool $isActive = true;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    #[ORM\Column]
    private string $passwordHash;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    public function __construct(string $email, string $normalizedEmail)
    {
        $this->id = new UuidV7();
        $this->email = $email;
        $this->normalizedEmail = $normalizedEmail;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function changeEmail(string $email, string $normalizedEmail): void
    {
        $this->email = $email;
        $this->normalizedEmail = $normalizedEmail;
    }

    public function getNormalizedEmail(): string
    {
        return $this->normalizedEmail;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function updateProfile(?string $firstName, ?string $lastName): void
    {
        $this->firstName = $firstName !== null ? trim($firstName) : null;
        $this->lastName = $lastName !== null ? trim($lastName) : null;
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
        return $this->normalizedEmail === mb_strtolower(trim($bootstrapEmail));
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return array_values(array_unique($roles));
    }

    public function grantAdmin(): void
    {
        $this->replaceRoles(['ROLE_ADMIN', 'ROLE_USER']);
    }

    public function assignRegularUser(): void
    {
        $this->replaceRoles(['ROLE_USER']);
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

    public function eraseCredentials(): void
    {
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        return $this->normalizedEmail === $user->normalizedEmail
            && $this->passwordHash === $user->passwordHash
            && $this->isActive === $user->isActive
            && $this->getRoles() === $user->getRoles();
    }

    public function getUserIdentifier(): string
    {
        return $this->normalizedEmail;
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

    /**
     * @param list<string> $roles
     */
    private function replaceRoles(array $roles): void
    {
        $cleanRoles = array_values(array_unique(array_filter(array_map('trim', $roles))));
        $this->roles = $cleanRoles === [] ? ['ROLE_USER'] : $cleanRoles;
    }
}
