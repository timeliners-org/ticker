<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $description
 * @property int $shift_category_id
 * @property \DateTime|null $start_time
 * @property \DateTime|null $end_time
 * @property int|null $connected_shift_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ShiftCategory $category
 * @property-read User $user
 * @property-read Shift|null $connectedShift
 */
#[Fillable(['user_id', 'description', 'shift_category_id', 'start_time', 'end_time', 'connected_shift_id'])]
class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    /** @return BelongsTo<ShiftCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ShiftCategory::class, 'shift_category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Connects a shift to one neighboring shift (e.g., a break or continuation)
     * The shift AFTER this
     *
     * @return BelongsTo<Shift, $this>
     */
    public function connectedShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'connected_shift_id');
    }
}
