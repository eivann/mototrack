<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait HasAuditTrail
{
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
