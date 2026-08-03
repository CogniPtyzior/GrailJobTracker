<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Maps a JSON HTTP request body to a typed request DTO and validates it through Symfony Validator.
 */
final class RequestPayloadMapper
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @template T of RequestPayload
     * @param class-string<T> $payloadClass
     * @return T
     */
    public function fromRequest(Request $request, string $payloadClass): RequestPayload
    {
        $payload = $this->decodeJsonObject($request);

        $this->rejectExtraFields($payload, $payloadClass::expectedFields());

        try {
            $dto = $payloadClass::fromArray($payload);
        } catch (RequestPayloadHydrationException|\TypeError) {
            throw new BadRequestHttpException('Invalid request payload.');
        }

        $violations = $this->validator->validate($dto);

        if (\count($violations) > 0) {
            $details = [];

            foreach ($violations as $violation) {
                $details[] = [
                    'path' => $this->apiPath((string) $violation->getPropertyPath()),
                    'message' => $violation->getMessage(),
                ];
            }

            throw new BadRequestHttpException(json_encode($details, JSON_THROW_ON_ERROR));
        }

        return $dto;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(Request $request): array
    {
        $payload = json_decode($request->getContent());

        if (!$payload instanceof \stdClass) {
            throw new BadRequestHttpException('Request body must be a valid JSON object.');
        }

        return $this->normalizeJsonValue($payload);
    }

    /**
     * @return mixed
     */
    private function normalizeJsonValue(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            return array_map($this->normalizeJsonValue(...), get_object_vars($value));
        }

        if (is_array($value)) {
            return array_map($this->normalizeJsonValue(...), $value);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $expectedFields
     */
    private function rejectExtraFields(array $payload, array $expectedFields): void
    {
        $extraFields = array_values(array_diff(array_keys($payload), $expectedFields));

        if ($extraFields === []) {
            return;
        }

        $details = array_map(static fn (string $field): array => [
            'path' => sprintf('[%s]', $field),
            'message' => 'This field was not expected.',
        ], $extraFields);

        throw new BadRequestHttpException(json_encode($details, JSON_THROW_ON_ERROR));
    }

    private function apiPath(string $propertyPath): string
    {
        if ($propertyPath === '') {
            return '';
        }

        return str_starts_with($propertyPath, '[') ? $propertyPath : sprintf('[%s]', $propertyPath);
    }
}


