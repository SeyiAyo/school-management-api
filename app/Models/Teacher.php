<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Teacher",
 *     type="object",
 *     required={"user_id", "qualification"},
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="user_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+1234567890"),
 *     @OA\Property(property="subject_specialty", type="string", nullable=true, example="Mathematics"),
 *     @OA\Property(
 *         property="qualification", 
 *         type="string", 
 *         enum={"High School Diploma", "Associate Degree", "Bachelor's Degree", "Master's Degree", "Doctorate (Ph.D.)", "Professional Certification", "Trade School Diploma", "Postgraduate Certificate/Diploma", "Other"},
 *         example="Master's Degree"
 *     ),
 *     @OA\Property(property="date_of_birth", type="string", format="date", nullable=true, example="1990-01-01"),
 *     @OA\Property(property="address", type="string", nullable=true, example="123 Main St, City, Country"),
 *     @OA\Property(property="gender", type="string", enum={"male", "female", "other"}, nullable=true, example="male"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="TeacherWithUser",
 *     type="object",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Teacher"),
 *         @OA\Schema(
 *             type="object",
 *             @OA\Property(
 *                 property="user",
 *                 type="object",
 *                 ref="#/components/schemas/User"
 *             )
 *         )
 *     }
 * )
 */

class Teacher extends Model
{
    use HasFactory;

    // Modern educational qualifications
    public const QUALIFICATION_HIGHSCHOOL = 'High School Diploma';
    public const QUALIFICATION_ASSOCIATE = 'Associate Degree';
    public const QUALIFICATION_BACHELORS = "Bachelor's Degree";
    public const QUALIFICATION_MASTERS = "Master's Degree";
    public const QUALIFICATION_DOCTORATE = 'Doctorate (Ph.D.)';
    public const QUALIFICATION_PROFESSIONAL = 'Professional Certification';
    public const QUALIFICATION_TRADE = 'Trade School Diploma';
    public const QUALIFICATION_POSTGRADUATE = 'Postgraduate Certificate/Diploma';
    public const QUALIFICATION_OTHER = 'Other';

    /**
     * Get all available qualifications
     *
     * @return array
     */
    public static function getQualifications(): array
    {
        return [
            self::QUALIFICATION_HIGHSCHOOL => 'High School Diploma',
            self::QUALIFICATION_ASSOCIATE => 'Associate Degree',
            self::QUALIFICATION_BACHELORS => "Bachelor's Degree",
            self::QUALIFICATION_MASTERS => "Master's Degree",
            self::QUALIFICATION_DOCTORATE => 'Doctorate (Ph.D.)',
            self::QUALIFICATION_PROFESSIONAL => 'Professional Certification',
            self::QUALIFICATION_TRADE => 'Trade School Diploma',
            self::QUALIFICATION_POSTGRADUATE => 'Postgraduate Certificate/Diploma',
            self::QUALIFICATION_OTHER => 'Other',
        ];
    }

    protected $fillable = [
        'user_id',
        'phone',
        'subject_specialty',
        'qualification',
        'date_of_birth',
        'address',
        'gender',
    ];
    
    /**
     * Get the qualification options for validation
     *
     * @return string
     */
    public static function getQualificationValidationRule(): string
    {
        return 'in:' . implode(',', array_keys(self::getQualifications()));
    }
    
    /**
     * Get the user that owns the teacher profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    /**
     * Get the classes taught by the teacher.
     */
    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }
}
