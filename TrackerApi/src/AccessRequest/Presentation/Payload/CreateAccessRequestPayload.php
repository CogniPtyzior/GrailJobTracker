<?php

declare(strict_types=1);

namespace App\AccessRequest\Presentation\Payload;

use App\AccessRequest\Application\Input\CreateAccessRequestInput;
use App\Shared\Infrastructure\Validation\RequestPayload;
use App\Shared\Infrastructure\Validation\RequestPayloadHydrationException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Typed DTO representing the public access request payload accepted by the HTTP controller.
 */
final readonly class CreateAccessRequestPayload implements RequestPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $companyName,
        #[Assert\NotBlank]
        #[Assert\Length(max: 5000)]
        public string $reason,
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
        return ['email', 'companyName', 'reason', 'firstName', 'lastName'];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        try {
            return new self(
                email: $payload['email'] ?? '',
                companyName: $payload['companyName'] ?? '',
                reason: $payload['reason'] ?? '',
                firstName: $payload['firstName'] ?? null,
                lastName: $payload['lastName'] ?? null,
            );
        } catch (\TypeError) {
            throw RequestPayloadHydrationException::invalidPayload();
        }
    }

    public function toInput(): CreateAccessRequestInput
    {
        return new CreateAccessRequestInput(
            email: $this->email,
            companyName: $this->companyName,
            reason: $this->reason,
            firstName: $this->firstName,
            lastName: $this->lastName,
        );
    }
}
