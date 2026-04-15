<?php

namespace App\ReferenceData\Presentation;

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
        return ApiJsonResponse::success([
            'contractTypes' => array_map(static fn (ContractType $item) => $item->value, ContractType::cases()),
            'remoteModes' => array_map(static fn (RemoteMode $item) => $item->value, RemoteMode::cases()),
            'trackedJobStatuses' => array_map(static fn (TrackedJobStatus $item) => $item->value, TrackedJobStatus::cases()),
            'defaultContractType' => ContractType::CDI->value,
        ]);
    }
}
