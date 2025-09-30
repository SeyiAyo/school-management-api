<?php

namespace App\Enums;

enum Role: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case STUDENT = 'student';
    case PARENT = 'parent';

    /**
     * Get all role values as array
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all role values as comma-separated string for validation
     *
     * @return string
     */
    public static function validationRule(): string
    {
        return 'in:' . implode(',', self::values());
    }

    /**
     * Get role display name
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::ADMIN => 'Administrator',
            self::TEACHER => 'Teacher',
            self::STUDENT => 'Student',
            self::PARENT => 'Parent',
        };
    }

    /**
     * Check if role is admin-level (admin or super_admin)
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::ADMIN, self::SUPER_ADMIN]);
    }

    /**
     * Check if role is super admin
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    /**
     * Get roles for dropdown options
     *
     * @return array
     */
    public static function options(): array
    {
        return collect(self::cases())->map(function ($role) {
            return [
                'value' => $role->value,
                'label' => $role->label()
            ];
        })->toArray();
    }
}
