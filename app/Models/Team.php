<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function members()
    {
        return $this->hasMany(TeamMember::class, 'team_id');
    }

    public function shiftCategories()
    {
        return $this->hasMany(ShiftCategory::class, 'owner_id');
    }
}
