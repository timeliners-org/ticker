<?php

namespace App\Data;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final readonly class TimerStatus
{
    public function __construct(
        public readonly CarbonInterface $createdAt,
        public CarbonInterface $startedAt,
        public int $shiftCategoryId,
        public ?CarbonInterface $pausedAt = null,
        public ?int $updateShiftId = null,
    ) {}

    public static function cacheKey(int|string $userId): string
    {
        return "user_status_{$userId}";
    }

    /**
     * @param  array{created_at: string, started_at: string, shift_category_id: int, paused_at?: string|null, update_shift_id?: int|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            createdAt: Carbon::parse($data['created_at']),
            startedAt: Carbon::parse($data['started_at']),
            shiftCategoryId: $data['shift_category_id'],
            pausedAt: isset($data['paused_at']) ? Carbon::parse($data['paused_at']) : null,
            updateShiftId: $data['update_shift_id'] ?? null,
        );
    }

    /**
     * @return array{created_at: string, started_at: string, shift_category_id: int, paused_at: string|null, update_shift_id: int|null}
     */
    public function toArray(): array
    {
        return [
            'created_at' => $this->createdAt->toIso8601String(),
            'started_at' => $this->startedAt->toIso8601String(),
            'shift_category_id' => $this->shiftCategoryId,
            'paused_at' => $this->pausedAt?->toIso8601String(),
            'update_shift_id' => $this->updateShiftId,
        ];
    }

    public function isPaused(): bool
    {
        return $this->pausedAt !== null;
    }

    public function updateTargetSet(): bool
    {
        return $this->updateShiftId !== null;
    }

    public function withPausedAt(CarbonInterface $pausedAt): self
    {
        return new self(
            createdAt: $this->createdAt,
            startedAt: $this->startedAt,
            shiftCategoryId: $this->shiftCategoryId,
            pausedAt: $pausedAt,
            updateShiftId: $this->updateShiftId,
        );
    }
}
