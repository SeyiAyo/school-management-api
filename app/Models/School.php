<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_user_id',
        'name',
        'type',
        'email',
        'phone',
        'address',
        'website',
        'logo_path',
        'description',
        'academic_levels',
        'calendar_structure',
        'status',
    ];

    protected $casts = [
        'academic_levels' => 'array',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
