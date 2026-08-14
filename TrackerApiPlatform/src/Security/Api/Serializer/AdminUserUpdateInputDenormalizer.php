<?php

declare(strict_types=1);

/*
 * Serializer adapter for admin user partial updates.
 * It records submitted fields during API Platform deserialization so validation can distinguish omission from null.
 */

namespace App\Security\Api\Serializer;

use App\Security\Api\Input\UpdateAdminUserInput;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class AdminUserUpdateInputDenormalizer implements DenormalizerInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): UpdateAdminUserInput
    {
        $payload = is_array($data) ? $data : [];
        $input = new UpdateAdminUserInput();
        $input->firstName = array_key_exists('firstName', $payload) ? $this->nullableString($payload['firstName']) : null;
        $input->lastName = array_key_exists('lastName', $payload) ? $this->nullableString($payload['lastName']) : null;
        $input->isActive = array_key_exists('isActive', $payload) ? $this->nullableBool($payload['isActive']) : null;
        $input->isAdmin = array_key_exists('isAdmin', $payload) ? $this->nullableBool($payload['isAdmin']) : null;
        $input->password = array_key_exists('password', $payload) ? $this->nullableString($payload['password'] ?? '') : null;
        $input->setProvidedFields(array_values(array_intersect(array_keys($payload), [
            'firstName',
            'lastName',
            'isActive',
            'isAdmin',
            'password',
        ])));

        return $input;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === UpdateAdminUserInput::class;
    }

    /**
     * @return array<class-string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [UpdateAdminUserInput::class => true];
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private function nullableBool(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }
}

