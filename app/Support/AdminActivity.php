<?php

namespace App\Support;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminActivity
{
    /**
     * @param array<string, mixed> $properties
     */
    public static function log(string $area, string $action, string $title, ?string $description = null, ?Model $subject = null, array $properties = []): void
    {
        try {
            if (! Schema::hasTable('admin_activity_logs')) {
                return;
            }

            $user = Auth::user();

            AdminActivityLog::create([
                'user_id' => $user?->getKey(),
                'user_name' => $user?->name,
                'area' => $area,
                'action' => $action,
                'title' => $title,
                'description' => $description,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => self::subjectLabel($subject),
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'properties' => $properties,
            ]);
        } catch (Throwable) {
            // Le journal ne doit jamais bloquer une action admin.
        }
    }

    private static function subjectLabel(?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        foreach (['title', 'name', 'label', 'email'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '#'.$subject->getKey();
    }
}
