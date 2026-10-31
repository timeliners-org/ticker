<?php

namespace App\Models;

use Database\Factories\ShiftCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftCategory extends Model
{
    /** @use HasFactory<ShiftCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'owner_id',
    ];

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'owner_id');
    }
}
