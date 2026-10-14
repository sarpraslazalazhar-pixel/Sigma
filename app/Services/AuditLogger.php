<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Catat log audit append-only.
     */
    public function log(
        string $action,
        mixed $entity = null,
        mixed $before = null,
        mixed $after = null,
        ?string $onBehalfOf = null
    ): AuditLog {
        $entityType = null;
        $entityId = null;

        if ($entity instanceof Model) {
            $entityType = get_class($entity);
            $entityId = $entity->getKey();
        } elseif (is_string($entity)) {
            $entityType = $entity;
        }

        return AuditLog::create([
            'user_id' => Auth::id(),
            'on_behalf_of' => $onBehalfOf,
            'action' => $action,
            'entity_type' => $entityType ?? 'System',
            'entity_id' => $entityId,
            'before' => is_array($before) || is_object($before) ? (array) $before : ($before !== null ? ['value' => $before] : null),
            'after' => is_array($after) || is_object($after) ? (array) $after : ($after !== null ? ['value' => $after] : null),
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
