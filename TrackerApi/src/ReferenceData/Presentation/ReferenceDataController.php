<?php

namespace App\ReferenceData\Presentation;

use App\ReferenceData\Presentation\View\ReferenceDataView;
use App\Shared\Infrastructure\Http\ApiJsonResponse;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reference-data')]
final class ReferenceDataController extends AbstractController
{
    #[OA\Get(path: '/api/reference-data', summary: 'Return enum values used by the frontend.', tags: ['Reference data'])]
    #[Route('', name: 'api_reference_data', methods: ['GET'])]
    public function __invoke(): Response
    {
        $view = new ReferenceDataView(
            contractTypes: ContractType::values(),
            remoteModes: RemoteMode::values(),
            trackedJobStatuses: TrackedJobStatus::values(),
            defaultContractType: ContractType::CDI->value,
        );

        return ApiJsonResponse::success($view->toArray());
    }
}
