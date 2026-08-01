<?php

namespace App\Support\Audit;

use App\Services\Audit\AuditLogger;

/**
 * Applied to models whose deletion is a sensitive action. On delete, the row's
 * attributes are captured to the audit log as the "before" state. Modules log
 * their own create/update audits explicitly through AuditLogger.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::deleted(function ($model): void {
            app(AuditLogger::class)->log(
                class_basename($model).'.deleted',
                $model,
                before: $model->getOriginal(),
            );
        });
    }
}
