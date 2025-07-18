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

    // Subject specialties
    public const SUBJECT_MATHEMATICS = 'Mathematics';
    public const SUBJECT_ENGLISH = 'English Language';
    public const SUBJECT_SCIENCE = 'Science';
    public const SUBJECT_PHYSICS = 'Physics';
    public const SUBJECT_CHEMISTRY = 'Chemistry';
    public const SUBJECT_BIOLOGY = 'Biology';
    public const SUBJECT_HISTORY = 'History';
    public const SUBJECT_GEOGRAPHY = 'Geography';
    public const SUBJECT_ECONOMICS = 'Economics';
    public const SUBJECT_GOVERNMENT = 'Government';
    public const SUBJECT_LITERATURE = 'Literature';
    public const SUBJECT_COMPUTER_SCIENCE = 'Computer Science';
    public const SUBJECT_ARTS = 'Arts';
    public const SUBJECT_MUSIC = 'Music';
    public const SUBJECT_PHYSICAL_EDUCATION = 'Physical Education';
    public const SUBJECT_FRENCH = 'French';
    public const SUBJECT_SPANISH = 'Spanish';
    public const SUBJECT_GERMAN = 'German';
    public const SUBJECT_ARABIC = 'Arabic';
    public const SUBJECT_RELIGIOUS_STUDIES = 'Religious Studies';
    public const SUBJECT_TECHNICAL_DRAWING = 'Technical Drawing';
    public const SUBJECT_HOME_ECONOMICS = 'Home Economics';
    public const SUBJECT_AGRICULTURE = 'Agriculture';
    public const SUBJECT_BUSINESS_STUDIES = 'Business Studies';
    public const SUBJECT_ACCOUNTING = 'Accounting';
    public const SUBJECT_OTHER = 'Other';

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

    /**
     * Get all available subject specialties
     *
     * @return array
     */
    public static function getSubjectSpecialties(): array
    {
        return [
            self::SUBJECT_MATHEMATICS => 'Mathematics',
            self::SUBJECT_ENGLISH => 'English Language',
            self::SUBJECT_SCIENCE => 'Science',
            self::SUBJECT_PHYSICS => 'Physics',
            self::SUBJECT_CHEMISTRY => 'Chemistry',
            self::SUBJECT_BIOLOGY => 'Biology',
            self::SUBJECT_HISTORY => 'History',
            self::SUBJECT_GEOGRAPHY => 'Geography',
            self::SUBJECT_ECONOMICS => 'Economics',
            self::SUBJECT_GOVERNMENT => 'Government',
            self::SUBJECT_LITERATURE => 'Literature',
            self::SUBJECT_COMPUTER_SCIENCE => 'Computer Science',
            self::SUBJECT_ARTS => 'Arts',
            self::SUBJECT_MUSIC => 'Music',
            self::SUBJECT_PHYSICAL_EDUCATION => 'Physical Education',
            self::SUBJECT_FRENCH => 'French',
            self::SUBJECT_SPANISH => 'Spanish',
            self::SUBJECT_GERMAN => 'German',
            self::SUBJECT_ARABIC => 'Arabic',
            self::SUBJECT_RELIGIOUS_STUDIES => 'Religious Studies',
            self::SUBJECT_TECHNICAL_DRAWING => 'Technical Drawing',
            self::SUBJECT_HOME_ECONOMICS => 'Home Economics',
            self::SUBJECT_AGRICULTURE => 'Agriculture',
            self::SUBJECT_BUSINESS_STUDIES => 'Business Studies',
            self::SUBJECT_ACCOUNTING => 'Accounting',
            self::SUBJECT_OTHER => 'Other',
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
     * Get the subject specialty options for validation
     *
     * @return string
     */
    public static function getSubjectSpecialtyValidationRule(): string
    {
        return 'in:' . implode(',', array_keys(self::getSubjectSpecialties()));
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
