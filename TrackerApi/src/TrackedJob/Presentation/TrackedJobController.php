<?php

namespace App\TrackedJob\Presentation;

use App\Security\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiJsonResponse;
use App\Shared\Infrastructure\Validation\PayloadValidator;
use App\TrackedJob\Application\TrackedJobCsvExporter;
use App\TrackedJob\Application\TrackedJobFactory;
use App\TrackedJob\Application\TrackedJobPresenter;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Infrastructure\Doctrine\TrackedJobRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api/tracked-jobs')]
final class TrackedJobController extends AbstractController
{
    public function __construct(
        private readonly PayloadValidator $payloadValidator,
        private readonly TrackedJobFactory $trackedJobFactory,
        private readonly TrackedJobPresenter $presenter,
        private readonly TrackedJobRepository $trackedJobRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TrackedJobCsvExporter $csvExporter,
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

        $result = $this->trackedJobRepository->search($user, $filters, $page, $pageSize);

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
            'items' => $this->trackedJobRepository->searchDistinctCompanies($user, $query),
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
        $payload = $this->payloadValidator->validateRequest($request, $this->trackedJobConstraint());

        $trackedJob = $this->trackedJobFactory->create($user, $payload);
        $this->entityManager->persist($trackedJob);
        $this->entityManager->flush();

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

        $payload = $this->payloadValidator->validateRequest($request, $this->trackedJobConstraint());
        $this->trackedJobFactory->hydrate($trackedJob, $payload);
        $this->entityManager->flush();

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

        $this->trackedJobRepository->delete($trackedJob);
        $this->entityManager->flush();

        return ApiJsonResponse::success(status: Response::HTTP_NO_CONTENT);
    }

    #[OA\Post(path: '/api/tracked-jobs/export-csv', summary: 'Export tracked jobs to CSV using active filters.', tags: ['Tracked jobs'])]
    #[Route('/export-csv', name: 'api_tracked_jobs_export_csv', methods: ['POST'])]
    public function exportCsv(Request $request, #[CurrentUser] User $user): Response
    {
        $payload = $this->payloadValidator->validateRequest($request, new Assert\Collection([
            'fields' => [
                'search' => new Assert\Optional([new Assert\Type('string')]),
                'company' => new Assert\Optional([new Assert\Type('string')]),
                'status' => new Assert\Optional([new Assert\Choice(array_map(static fn (TrackedJobStatus $item) => $item->value, TrackedJobStatus::cases()))]),
                'contractType' => new Assert\Optional([new Assert\Choice(array_map(static fn (ContractType $item) => $item->value, ContractType::cases()))]),
                'remoteMode' => new Assert\Optional([new Assert\Choice(array_map(static fn (RemoteMode $item) => $item->value, RemoteMode::cases()))]),
            ],
            'allowMissingFields' => true,
            'allowExtraFields' => false,
        ]));

        $filters = [
            'search' => $payload['search'] ?? null,
            'company' => $payload['company'] ?? null,
            'status' => isset($payload['status']) ? TrackedJobStatus::tryFrom((string) $payload['status']) : null,
            'contractType' => isset($payload['contractType']) ? ContractType::tryFrom((string) $payload['contractType']) : null,
            'remoteMode' => isset($payload['remoteMode']) ? RemoteMode::tryFrom((string) $payload['remoteMode']) : null,
        ];

        $result = $this->trackedJobRepository->search($user, $filters, 1, 5000);
        $csv = $this->csvExporter->export($result['items']);

        return new Response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="tracked-jobs.csv"',
        ]);
    }

    private function findTrackedJob(string $id, User $user): ?TrackedJob
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->trackedJobRepository->getByIdForOwner(Uuid::fromString($id), $user);
    }

    private function trackedJobConstraint(): Assert\Collection
    {
        return new Assert\Collection([
            'fields' => [
                'company' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'title' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'contractType' => new Assert\Optional([new Assert\Choice(array_map(static fn (ContractType $item) => $item->value, ContractType::cases()))]),
                'location' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'remoteMode' => new Assert\Optional([new Assert\Choice(array_map(static fn (RemoteMode $item) => $item->value, RemoteMode::cases()))]),
                'remuneration' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'offerUrl' => new Assert\Optional([new Assert\Type('string')]),
                'notes' => new Assert\Optional([new Assert\Type('string')]),
                'applicationDate' => new Assert\Optional([new Assert\Type('string')]),
                'effectiveFollowUpDate' => new Assert\Optional([new Assert\Type('string')]),
                'firstContactDate' => new Assert\Optional([new Assert\Type('string')]),
                'preliminaryInterviewDate' => new Assert\Optional([new Assert\Type('string')]),
                'secondInterviewDate' => new Assert\Optional([new Assert\Type('string')]),
                'hrContactName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'businessContactName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'subjectiveRelevance' => new Assert\Optional([new Assert\Type('numeric'), new Assert\Range(min: 1, max: 10)]),
                'status' => new Assert\Optional([new Assert\Choice(array_map(static fn (TrackedJobStatus $item) => $item->value, TrackedJobStatus::cases()))]),
            ],
            'allowMissingFields' => true,
            'allowExtraFields' => false,
        ]);
    }
}
