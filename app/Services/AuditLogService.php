<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(string $action, ?string $entityType = null, ?int $entityId = null, array $before = [], array $after = [], ?string $reason = null): void
    {
        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'route' => request()?->path(),
            'ip_address' => request()?->ip(),
            'before_state' => empty($before) ? null : $before,
            'after_state' => empty($after) ? null : $after,
            'reason' => $reason,
        ]);
    }
}
