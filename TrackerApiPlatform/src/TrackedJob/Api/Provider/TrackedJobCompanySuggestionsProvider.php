<?php

declare(strict_types=1);

/*
 * API Platform provider for tracked job company suggestions.
 * It keeps autocomplete query handling in the inbound adapter and delegates owner-scoped lookup to the application layer.
 */

namespace App\TrackedJob\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\TrackedJob\Api\Output\TrackedJobCompanySuggestionsOutput;
use App\TrackedJob\Application\UseCase\SuggestTrackedJobCompanies;
use Symfony\Component\HttpFoundation\RequestStack;

/** @implements ProviderInterface<TrackedJobCompanySuggestionsOutput> */
final readonly class TrackedJobCompanySuggestionsProvider implements ProviderInterface
{
    public function __construct(
        private AuthenticatedUserResolver $authenticatedUserResolver,
        private SuggestTrackedJobCompanies $suggestTrackedJobCompanies,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TrackedJobCompanySuggestionsOutput
    {
        $query = trim((string) ($this->requestStack->getCurrentRequest()?->query->get('q', '') ?? ''));

        if (mb_strlen($query) < 3) {
            return new TrackedJobCompanySuggestionsOutput([]);
        }

        return new TrackedJobCompanySuggestionsOutput(
            $this->suggestTrackedJobCompanies->handle($this->authenticatedUserResolver->requireUser(), $query),
        );
    }
}
