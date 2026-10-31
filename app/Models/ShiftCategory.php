<?php

namespace App\Models;

use Database\Factories\ShiftCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftCategory extends Model
{
    /** @use HasFactory<ShiftCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'owner_id',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'owner_id');
    }
}
