<?php

declare(strict_types=1);

namespace ChrisJohnLeah\Joblogic\Data;

final readonly class JoblogicPage
{
    /**
     * @param  array<int, mixed>  $items
     */
    public function __construct(
        public array $items,
        public int $pageIndex,
        public int $pageSize,
        public ?int $totalCount = null,
        public int $status = 200,
        public mixed $payload = null,
    ) {}

    public static function fromResponse(JoblogicResponse $response, int $pageIndex, int $pageSize): self
    {
        $payload = $response->data;
        $items = self::itemsFrom($payload);
        $totalCount = self::integerFrom($payload, [
            'TotalCount',
            'totalCount',
            'TotalRecords',
            'totalRecords',
            'RecordCount',
            'recordCount',
        ]);

        return new self($items, $pageIndex, $pageSize, $totalCount, $response->status, $payload);
    }

    public function hasMore(): bool
    {
        if ($this->totalCount !== null) {
            return ($this->pageIndex * $this->pageSize) < $this->totalCount;
        }

        return count($this->items) >= $this->pageSize;
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function failed(): bool
    {
        return ! $this->successful();
    }

    public function nextPageIndex(): ?int
    {
        return $this->hasMore() ? $this->pageIndex + 1 : null;
    }

    /**
     * Return the provider's original page payload for migration-specific
     * metadata while keeping the common collection operations typed.
     */
    public function json(): mixed
    {
        return $this->payload;
    }

    /**
     * @return array<int, mixed>
     */
    private static function itemsFrom(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (array_is_list($payload)) {
            return array_values($payload);
        }

        foreach (['Items', 'items', 'Records', 'records', 'Results', 'results', 'Data', 'data'] as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];

            if (is_array($value) && array_is_list($value)) {
                return array_values($value);
            }

            $nested = self::itemsFrom($value);
            if ($nested !== []) {
                return $nested;
            }
        }

        return [$payload];
    }

    /**
     * @param  array<int, string>  $keys
     */
    private static function integerFrom(mixed $payload, array $keys): ?int
    {
        if (! is_array($payload)) {
            return null;
        }

        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_numeric($payload[$key])) {
                return (int) $payload[$key];
            }
        }

        foreach (['Data', 'data', 'Meta', 'meta', 'Paging', 'paging'] as $key) {
            if (isset($payload[$key])) {
                $nested = self::integerFrom($payload[$key], $keys);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }
}
