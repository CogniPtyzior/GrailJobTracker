<?php

declare(strict_types=1);

/*
 * API adapter enforcing public access request submission throttling.
 * It keeps Symfony RateLimiter concerns outside processors and application use cases.
 */

namespace App\AccessRequest\Api\RateLimit;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class AccessRequestSubmissionLimiter implements AccessRequestSubmissionLimiterInterface
{
    public function __construct(
        private RequestStack $requestStack,
        #[Autowire(service: 'limiter.access_request_submission')]
        private RateLimiterFactory $limiterFactory,
    ) {
    }

    public function enforce(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $key = hash('sha256', (string) $request?->getClientIp());
        $limit = $this->limiterFactory->create($key)->consume();

        if ($limit->isAccepted()) {
            return;
        }

        $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());

        throw new TooManyRequestsHttpException(
            $retryAfter,
            'Too many access request submissions. Please retry later.',
        );
    }
}
