<?php

namespace App\Models;

use Database\Factories\QuotaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
