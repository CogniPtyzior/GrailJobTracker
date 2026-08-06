<?php

namespace App\AccessRequest\Presentation\View;

final readonly class AccessRequestListView
{
    /**
     * @param list<AccessRequestView> $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $pageSize,
        public int $total,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(static fn (AccessRequestView $item): array => $item->toArray(), $this->items),
            'page' => $this->page,
            'pageSize' => $this->pageSize,
            'total' => $this->total,
        ];
    }
}