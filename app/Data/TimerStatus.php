<?php

namespace App\Data;

use Carbon\CarbonInterface;

final readonly class TimerStatus
{
    public function __construct(
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
     * @param  array{started_at: CarbonInterface, shift_category_id: int, paused_at?: CarbonInterface|null, update_shift_id?: int|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            startedAt: $data['started_at'],
            shiftCategoryId: $data['shift_category_id'],
            pausedAt: $data['paused_at'] ?? null,
            updateShiftId: $data['update_shift_id'] ?? null,
        );
    }

    /**
     * @return array{started_at: CarbonInterface, shift_category_id: int, paused_at: CarbonInterface|null, update_shift_id: int|null}
     */
    public function toArray(): array
    {
        return [
            'started_at' => $this->startedAt,
            'shift_category_id' => $this->shiftCategoryId,
            'paused_at' => $this->pausedAt,
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
        return new self($this->startedAt, $this->shiftCategoryId, $pausedAt, $this->updateShiftId);
    }
}
