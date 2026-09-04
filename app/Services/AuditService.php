<?php

namespace App\Services;

use App\Models\{AuditLog, Brand};
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function record(string $action, ?Model $auditable=null, array $before=[], array $after=[], ?int $brandId=null): AuditLog
    {
        return AuditLog::create([
            'user_id'=>auth()->id(),
            'brand_id'=>$brandId ?? data_get($auditable,'brand_id') ?? ($auditable instanceof Brand?$auditable->id:null),
            'action'=>$action,
            'auditable_type'=>$auditable?->getMorphClass(),
            'auditable_id'=>$auditable?->getKey(),
            'before'=>$this->clean($before) ?: null,
            'after'=>$this->clean($after) ?: null,
            'ip_address'=>request()?->ip(),
            'user_agent'=>(string) str(request()?->userAgent())->limit(1000),
        ]);
    }

    private function clean(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (preg_match('/password|token|secret|session|cookie|file(_content)?/i', (string) $key)) continue;
            $clean[$key] = is_array($value) ? $this->clean($value) : $value;
        }
        return $clean;
    }
}
