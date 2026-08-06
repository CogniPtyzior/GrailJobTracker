<?php

declare(strict_types=1);

namespace App\Admin\Presentation\Payload;

use App\Admin\Application\Input\UpdateAdminUserInput;
use App\Shared\Domain\ValueObject\PersonName;
use App\Shared\Infrastructure\Validation\RequestPayload;
use App\Shared\Infrastructure\Validation\RequestPayloadHydrationException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Typed DTO representing the partial admin user update payload accepted by the HTTP controller.
 */
final readonly class UpdateAdminUserPayload implements RequestPayload
{
    /**
     * @param list<string> $providedFields
     */
    public function __construct(
        #[Assert\Length(max: 120)]
        public ?string $firstName = null,
        #[Assert\Length(max: 120)]
        public ?string $lastName = null,
        #[Assert\When(
            expression: 'this.has("isActive")',
            constraints: [
                new Assert\NotNull(),
            ],
        )]
        public ?bool $isActive = null,
        #[Assert\When(
            expression: 'this.has("isAdmin")',
            constraints: [
                new Assert\NotNull(),
            ],
        )]
        public ?bool $isAdmin = null,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(min: 8)]
        #[Assert\Regex('/\d/', message: 'Password must contain at least one digit.')]
        #[Assert\Regex('/[.#&!]/', message: 'Password must contain at least one allowed special character: . # & !')]
        public ?string $password = null,
        private array $providedFields = [],
    ) {
    }

    /**
     * @return list<string>
     */
    public static function expectedFields(): array
    {
        return ['firstName', 'lastName', 'isActive', 'isAdmin', 'password'];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        try {
            return new self(
                firstName: $payload['firstName'] ?? null,
                lastName: $payload['lastName'] ?? null,
                isActive: $payload['isActive'] ?? null,
                isAdmin: $payload['isAdmin'] ?? null,
                password: array_key_exists('password', $payload) ? ($payload['password'] ?? '') : null,
                providedFields: array_values(array_intersect(array_keys($payload), self::expectedFields())),
            );
        } catch (\TypeError) {
            throw RequestPayloadHydrationException::invalidPayload();
        }
    }

    public function toInput(): UpdateAdminUserInput
    {
        return new UpdateAdminUserInput(
            firstName: PersonName::fromNullable($this->firstName),
            lastName: PersonName::fromNullable($this->lastName),
            isActive: $this->isActive,
            isAdmin: $this->isAdmin,
            password: $this->password,
            providedFields: $this->providedFields,
        );
    }

    public function has(string $field): bool
    {
        return in_array($field, $this->providedFields, true);
    }
}

