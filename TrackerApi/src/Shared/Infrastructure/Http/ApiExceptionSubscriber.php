<?php

namespace App\Shared\Infrastructure\Http;

use JsonException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $throwable = $event->getThrowable();
        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'Unexpected server error.';
        $details = [];

        if ($throwable instanceof AccessDeniedHttpException) {
            $status = Response::HTTP_FORBIDDEN;
            $message = 'Access denied.';
        } elseif ($throwable instanceof BadRequestHttpException) {
            $status = Response::HTTP_BAD_REQUEST;
            $message = 'Invalid request payload.';

            try {
                $details = json_decode((string) $throwable->getMessage(), true, 512, JSON_THROW_ON_ERROR);

                if (!is_array($details)) {
                    $details = [];
                }
            } catch (JsonException) {
                $message = $throwable->getMessage();
            }
        } elseif ($throwable instanceof HttpExceptionInterface) {
            $status = $throwable->getStatusCode();
            $message = $throwable->getMessage() ?: $message;
        } else {
            $message = $throwable->getMessage() ?: $message;
        }

        $event->setResponse(ApiJsonResponse::error($message, $status, $details));
    }
}
