<?php

declare(strict_types=1);

namespace App\Admin\Presentation\Payload;

use App\Admin\Application\Input\CreateAdminUserInput;
use App\Shared\Domain\ValueObject\PersonName;
use App\Shared\Infrastructure\Validation\RequestPayload;
use App\Shared\Infrastructure\Validation\RequestPayloadHydrationException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Typed DTO representing the admin user creation payload accepted by the HTTP controller.
 */
final readonly class CreateAdminUserPayload implements RequestPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        #[Assert\Regex('/\d/', message: 'Password must contain at least one digit.')]
        #[Assert\Regex('/[.#&!]/', message: 'Password must contain at least one allowed special character: . # & !')]
        public string $password,
        #[Assert\Length(max: 120)]
        public ?string $firstName = null,
        #[Assert\Length(max: 120)]
        public ?string $lastName = null,
        public bool $isActive = true,
        public bool $isAdmin = false,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function expectedFields(): array
    {
        return ['email', 'password', 'firstName', 'lastName', 'isActive', 'isAdmin'];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        try {
            return new self(
                email: $payload['email'] ?? '',
                password: $payload['password'] ?? '',
                firstName: $payload['firstName'] ?? null,
                lastName: $payload['lastName'] ?? null,
                isActive: $payload['isActive'] ?? true,
                isAdmin: $payload['isAdmin'] ?? false,
            );
        } catch (\TypeError) {
            throw RequestPayloadHydrationException::invalidPayload();
        }
    }

    public function toInput(): CreateAdminUserInput
    {
        return new CreateAdminUserInput(
            email: $this->email,
            password: $this->password,
            firstName: PersonName::fromNullable($this->firstName),
            lastName: PersonName::fromNullable($this->lastName),
            isActive: $this->isActive,
            isAdmin: $this->isAdmin,
        );
    }
}

