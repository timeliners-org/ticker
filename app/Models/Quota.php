<?php

namespace App\Models;

use Database\Factories\QuotaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $date
 * @property int $quota
 */
#[Fillable(['date', 'quota', 'user_id'])]
class Quota extends Model
{
    /** @use HasFactory<QuotaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'quota' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
