<?php

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiJsonResponse
{
    public static function success(array $data = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    public static function error(string $message, int $status = Response::HTTP_BAD_REQUEST, array $details = []): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'details' => $details,
        ], $status);
    }
}
