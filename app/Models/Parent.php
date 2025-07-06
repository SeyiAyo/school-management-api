<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'parents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'phone',
        'address',
    ];

    /**
     * Get the user that owns the parent profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the students associated with the parent.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
