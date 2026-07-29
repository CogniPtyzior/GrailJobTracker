<?php

namespace App\TrackedJob\Presentation;

use App\Security\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiJsonResponse;
use App\Shared\Infrastructure\Validation\PayloadValidator;
use App\TrackedJob\Application\CreateTrackedJob;
use App\TrackedJob\Application\DeleteTrackedJob;
use App\TrackedJob\Application\ExportTrackedJobsCsv;
use App\TrackedJob\Application\GetTrackedJob;
use App\TrackedJob\Application\SearchTrackedJobs;
use App\TrackedJob\Application\SuggestTrackedJobCompanies;
use App\TrackedJob\Application\TrackedJobPresenter;
use App\TrackedJob\Application\UpdateTrackedJob;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Presentation\Payload\ExportTrackedJobsPayload;
use App\TrackedJob\Presentation\Payload\TrackedJobPayload;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/tracked-jobs')]
final class TrackedJobController extends AbstractController
{
    public function __construct(
        private readonly PayloadValidator $payloadValidator,
        private readonly TrackedJobPresenter $presenter,
        private readonly SearchTrackedJobs $searchTrackedJobs,
        private readonly SuggestTrackedJobCompanies $suggestTrackedJobCompanies,
        private readonly GetTrackedJob $getTrackedJob,
        private readonly ExportTrackedJobsCsv $exportTrackedJobsCsv,
        private readonly CreateTrackedJob $createTrackedJob,
        private readonly UpdateTrackedJob $updateTrackedJob,
        private readonly DeleteTrackedJob $deleteTrackedJob,
    ) {
    }

    #[OA\Get(path: '/api/tracked-jobs', summary: 'List tracked jobs.', tags: ['Tracked jobs'])]
    #[Route('', name: 'api_tracked_jobs_list', methods: ['GET'])]
    public function list(Request $request, #[CurrentUser] User $user): Response
    {
        $page = max((int) $request->query->get('page', 1), 1);
        $pageSize = min(max((int) $request->query->get('pageSize', 10), 1), 100);
        $statusRaw = trim((string) $request->query->get('status', ''));
        $contractTypeRaw = trim((string) $request->query->get('contractType', ''));
        $remoteModeRaw = trim((string) $request->query->get('remoteMode', ''));

        $status = TrackedJobStatus::tryFrom($statusRaw);
        $contractType = ContractType::tryFrom($contractTypeRaw);
        $remoteMode = RemoteMode::tryFrom($remoteModeRaw);

        $filters = [
            'search' => $request->query->get('search'),
            'company' => $request->query->get('company'),
            'status' => $status,
            'contractType' => $contractType,
            'remoteMode' => $remoteMode,
            'statusInvalid' => $statusRaw !== '' && $status === null,
            'contractTypeInvalid' => $contractTypeRaw !== '' && $contractType === null,
            'remoteModeInvalid' => $remoteModeRaw !== '' && $remoteMode === null,
        ];

        $result = $this->searchTrackedJobs->handle($user, $filters, $page, $pageSize);

        return ApiJsonResponse::success([
            'items' => array_map($this->presenter->present(...), $result['items']),
            'page' => $page,
            'pageSize' => $pageSize,
            'hasMore' => $result['hasMore'],
        ]);
    }

    #[OA\Get(path: '/api/tracked-jobs/company-suggestions', summary: 'Return company suggestions.', tags: ['Tracked jobs'])]
    #[Route('/company-suggestions', name: 'api_tracked_jobs_company_suggestions', methods: ['GET'])]
    public function companySuggestions(Request $request, #[CurrentUser] User $user): Response
    {
        $query = trim((string) $request->query->get('q', ''));

        if (mb_strlen($query) < 3) {
            return ApiJsonResponse::success(['items' => []]);
        }

        return ApiJsonResponse::success([
            'items' => $this->suggestTrackedJobCompanies->handle($user, $query),
        ]);
    }

    #[OA\Get(path: '/api/tracked-jobs/{id}', summary: 'Get a tracked job.', tags: ['Tracked jobs'])]
    #[Route('/{id}', name: 'api_tracked_jobs_get', methods: ['GET'])]
    public function get(string $id, #[CurrentUser] User $user): Response
    {
        $trackedJob = $this->findTrackedJob($id, $user);

        if (!$trackedJob instanceof TrackedJob) {
            return ApiJsonResponse::error('Tracked job not found.', Response::HTTP_NOT_FOUND);
        }

        return ApiJsonResponse::success([
            'item' => $this->presenter->present($trackedJob),
        ]);
    }

    #[OA\Post(path: '/api/tracked-jobs', summary: 'Create a tracked job.', tags: ['Tracked jobs'])]
    #[Route('', name: 'api_tracked_jobs_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): Response
    {
        $payload = $this->payloadValidator->validateRequest($request, TrackedJobPayload::constraint());
        $trackedJob = $this->createTrackedJob->handle($user, $payload);

        return ApiJsonResponse::success([
            'item' => $this->presenter->present($trackedJob),
        ], Response::HTTP_CREATED);
    }

    #[OA\Put(path: '/api/tracked-jobs/{id}', summary: 'Update a tracked job.', tags: ['Tracked jobs'])]
    #[Route('/{id}', name: 'api_tracked_jobs_update', methods: ['PUT'])]
    public function update(string $id, Request $request, #[CurrentUser] User $user): Response
    {
        $trackedJob = $this->findTrackedJob($id, $user);

        if (!$trackedJob instanceof TrackedJob) {
            return ApiJsonResponse::error('Tracked job not found.', Response::HTTP_NOT_FOUND);
        }

        $payload = $this->payloadValidator->validateRequest($request, TrackedJobPayload::constraint());
        $this->updateTrackedJob->handle($trackedJob, $payload);

        return ApiJsonResponse::success([
            'item' => $this->presenter->present($trackedJob),
        ]);
    }

    #[OA\Delete(path: '/api/tracked-jobs/{id}', summary: 'Delete a tracked job.', tags: ['Tracked jobs'])]
    #[Route('/{id}', name: 'api_tracked_jobs_delete', methods: ['DELETE'])]
    public function delete(string $id, #[CurrentUser] User $user): Response
    {
        $trackedJob = $this->findTrackedJob($id, $user);

        if (!$trackedJob instanceof TrackedJob) {
            return ApiJsonResponse::error('Tracked job not found.', Response::HTTP_NOT_FOUND);
        }

        $this->deleteTrackedJob->handle($trackedJob);

        return ApiJsonResponse::success(status: Response::HTTP_NO_CONTENT);
    }

    #[OA\Post(path: '/api/tracked-jobs/export-csv', summary: 'Export tracked jobs to CSV using active filters.', tags: ['Tracked jobs'])]
    #[Route('/export-csv', name: 'api_tracked_jobs_export_csv', methods: ['POST'])]
    public function exportCsv(Request $request, #[CurrentUser] User $user): Response
    {
        $payload = $this->payloadValidator->validateRequest($request, ExportTrackedJobsPayload::constraint());

        $filters = [
            'search' => $payload['search'] ?? null,
            'company' => $payload['company'] ?? null,
            'status' => isset($payload['status']) ? TrackedJobStatus::tryFrom((string) $payload['status']) : null,
            'contractType' => isset($payload['contractType']) ? ContractType::tryFrom((string) $payload['contractType']) : null,
            'remoteMode' => isset($payload['remoteMode']) ? RemoteMode::tryFrom((string) $payload['remoteMode']) : null,
        ];

        $csv = $this->exportTrackedJobsCsv->handle($user, $filters);

        return new Response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="tracked-jobs.csv"',
        ]);
    }

    private function findTrackedJob(string $id, User $user): ?TrackedJob
    {
        return $this->getTrackedJob->handle($id, $user);
    }
}
