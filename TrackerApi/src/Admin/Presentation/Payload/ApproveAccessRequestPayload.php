<?php

declare(strict_types=1);

namespace App\Admin\Presentation\Payload;

use App\Admin\Application\Input\ApproveAccessRequestInput;
use App\Shared\Infrastructure\Validation\RequestPayload;
use App\Shared\Infrastructure\Validation\RequestPayloadHydrationException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Typed DTO representing the access request approval payload accepted by the HTTP controller.
 */
final readonly class ApproveAccessRequestPayload implements RequestPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        #[Assert\Regex('/\d/', message: 'Password must contain at least one digit.')]
        #[Assert\Regex('/[.#&!]/', message: 'Password must contain at least one allowed special character: . # & !')]
        public string $password,
        #[Assert\Length(max: 120)]
        public ?string $firstName = null,
        #[Assert\Length(max: 120)]
        public ?string $lastName = null,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function expectedFields(): array
    {
        return ['password', 'firstName', 'lastName'];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        try {
            return new self(
                password: $payload['password'] ?? '',
                firstName: $payload['firstName'] ?? null,
                lastName: $payload['lastName'] ?? null,
            );
        } catch (\TypeError) {
            throw RequestPayloadHydrationException::invalidPayload();
        }
    }

    public function toInput(): ApproveAccessRequestInput
    {
        return new ApproveAccessRequestInput(
            password: $this->password,
            firstName: $this->firstName,
            lastName: $this->lastName,
        );
    }
}
