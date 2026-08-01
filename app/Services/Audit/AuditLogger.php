<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * One call records who did what to which record, with before and after values
 * on sensitive actions. Wired into role changes, settings changes and record
 * deletions in Phase 3; every later module reuses it.
 */
class AuditLogger
{
    public function log(string $action, ?Model $subject = null, ?array $before = null, ?array $after = null): AuditLog
    {
        return AuditLog::create([
            'institute_id' => $this->instituteId($subject),
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
            'ip' => $this->safeRequest(fn ($r) => $r->ip()),
            'user_agent' => mb_substr((string) $this->safeRequest(fn ($r) => $r->userAgent()), 0, 255) ?: null,
        ]);
    }

    /**
     * Record an update, capturing only the changed attributes' before/after.
     */
    public function logChange(string $action, Model $subject, array $original): AuditLog
    {
        $changed = array_keys($subject->getChanges());
        $before = [];
        $after = [];

        foreach ($changed as $key) {
            if ($key === 'updated_at') {
                continue;
            }
            $before[$key] = $original[$key] ?? null;
            $after[$key] = $subject->getAttribute($key);
        }

        return $this->log($action, $subject, $before ?: null, $after ?: null);
    }

    private function instituteId(?Model $subject): ?int
    {
        return $subject && isset($subject->institute_id) ? (int) $subject->institute_id : null;
    }

    private function safeRequest(callable $fn): mixed
    {
        try {
            return app()->bound('request') ? $fn(request()) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
