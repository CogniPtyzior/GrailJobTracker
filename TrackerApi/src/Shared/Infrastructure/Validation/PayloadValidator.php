<?php

namespace App\Shared\Infrastructure\Validation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PayloadValidator
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function validateRequest(Request $request, Constraint $constraint): array
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Request body must be a valid JSON object.');
        }

        $violations = $this->validator->validate($payload, $constraint);

        if (\count($violations) > 0) {
            $details = [];

            foreach ($violations as $violation) {
                $details[] = [
                    'path' => (string) $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            throw new BadRequestHttpException(json_encode($details, JSON_THROW_ON_ERROR));
        }

        return $payload;
    }
}
