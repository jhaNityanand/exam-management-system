<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasAuditTrails
{
    protected static function bootHasAuditTrails()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                if (empty($model->created_by)) {
                    $model->created_by = Auth::id();
                }
                if (empty($model->updated_by)) {
                    $model->updated_by = Auth::id();
                }
            }
        });

        static::updating(function ($model) {
            if (! Auth::check() || ! $model->isDirty()) {
                return;
            }

            $model->updated_by = Auth::id();

            $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());
            if (! in_array('updated_by_history', $columns, true)) {
                return;
            }

            $raw = $model->getRawOriginal('updated_by_history');
            $history = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);
            $uid = Auth::id();
            $last = end($history);
            $lastUid = is_array($last) ? ($last['user_id'] ?? null) : $last;
            if ((int) $lastUid !== (int) $uid) {
                $history[] = ['user_id' => $uid, 'at' => now()->toIso8601String()];
            }
            $model->updated_by_history = $history;
        });
    }
}
