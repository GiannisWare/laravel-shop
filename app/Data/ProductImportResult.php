<?php

namespace App\Data;

final readonly class ProductImportResult
{
    /**
     * @param  list<array{row: int, attribute: string, messages: list<string>}>  $failures
     */
    public function __construct(
        public int $created,
        public int $updated,
        public array $failures,
    ) {}

    public function imported(): int
    {
        return $this->created + $this->updated;
    }

    public function failureSummary(int $limit = 10): string
    {
        if ($this->failures === []) {
            return 'No rows failed validation.';
        }

        $summary = collect($this->failures)
            ->take($limit)
            ->map(fn (array $failure): string => sprintf(
                'Row %d (%s): %s',
                $failure['row'],
                $failure['attribute'],
                implode(' ', $failure['messages']),
            ))
            ->implode("\n");

        $remaining = count($this->failures) - $limit;

        return $remaining > 0
            ? $summary."\n…and {$remaining} more failure(s)."
            : $summary;
    }
}
