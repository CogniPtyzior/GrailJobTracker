<?php

namespace App\TrackedJob\Presentation\View;

final readonly class TrackedJobSearchView
{
    /**
     * @param list<TrackedJobView> $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $pageSize,
        public bool $hasMore,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(static fn (TrackedJobView $item): array => $item->toArray(), $this->items),
            'page' => $this->page,
            'pageSize' => $this->pageSize,
            'hasMore' => $this->hasMore,
        ];
    }
}