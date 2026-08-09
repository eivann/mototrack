<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Audit #14: audit rows are written synchronously, inside whatever DB
 * transaction the audited change runs in. This is deliberate, not an oversight:
 *
 *   - Atomicity: if a checkout/commit rolls back, its audit rows must roll back
 *     with it. Writing them asynchronously or after commit would record actions
 *     that never actually happened (or lose the record if the async write fails).
 *   - Granularity: callers and tests rely on exactly one row per model event,
 *     read back through Eloquent casts (see Admin/AuditLogTest). A bulk insert
 *     would bypass the created_at hook and array→json casting.
 *
 * The extra inserts are bounded by cart size (a handful of items per POS/online
 * order), so the throughput cost is negligible at this application's scale.
 */
trait HasAuditTrail
{
    public ?array $audit_temp_changes = null;

    public static function bootHasAuditTrail(): void
    {
        static::created(function ($model) {
            static::logAudit('created', $model, null, $model->getAttributes());
        });

        static::updating(function ($model) {
            // Suppress log duplication on soft delete (which triggers update of deleted_at)
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting() && $model->isDirty('deleted_at')) {
                return;
            }

            $dirty = $model->getDirty();
            $old = [];
            $new = [];
            
            foreach ($dirty as $key => $value) {
                $old[$key] = $model->getRawOriginal($key);
                $new[$key] = $value;
            }

            $model->audit_temp_changes = [
                'old' => $old,
                'new' => $new,
            ];
        });

        static::updated(function ($model) {
            if (isset($model->audit_temp_changes)) {
                static::logAudit('updated', $model, $model->audit_temp_changes['old'], $model->audit_temp_changes['new']);
            }
        });

        static::deleted(function ($model) {
            $action = 'deleted';
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                $action = 'soft_deleted';
            }
            static::logAudit($action, $model, $model->getAttributes(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                static::logAudit('restored', $model, null, $model->getAttributes());
            });
        }
    }

    protected static function logAudit(string $action, $model, ?array $old, ?array $new): void
    {
        $hiddenFields = method_exists($model, 'getHidden') ? $model->getHidden() : [];
        
        if (!empty($hiddenFields)) {
            if ($old) {
                $old = array_diff_key($old, array_flip($hiddenFields));
            }
            if ($new) {
                $new = array_diff_key($new, array_flip($hiddenFields));
            }
        }

        // Clean up metadata updates
        if ($action === 'updated') {
            unset($old['updated_at'], $new['updated_at']);
            if (empty($new)) {
                return; // No auditable data changed
            }
        }

        AuditLog::create([
            'user_id'        => Auth::id(),
            'action'         => $action,
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->id,
            'old_values'     => $old,
            'new_values'     => $new,
            'ip_address'     => Request::ip() ?? '127.0.0.1',
            'user_agent'     => Request::userAgent(),
        ]);
    }
}
