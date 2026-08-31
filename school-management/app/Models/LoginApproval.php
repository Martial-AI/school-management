<?php

namespace App\Models;

use App\Services\DeviceNameResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginApproval extends Model
{
    protected $fillable = ['user_id', 'current_session_id', 'requester_session_id', 'requester_ip_address', 'requester_user_agent', 'requester_device_model', 'requester_device_platform', 'requester_device_browser', 'remember', 'status', 'expires_at', 'resolved_at'];
    protected function casts(): array { return ['remember' => 'boolean', 'expires_at' => 'datetime', 'resolved_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function getRequesterDeviceNameAttribute(): string { return $this->requester_device_model ?: DeviceNameResolver::modelFromUserAgent($this->requester_user_agent) ?: ($this->requester_device_browser ?: DeviceNameResolver::fromUserAgent($this->requester_user_agent)); }
    public function getRequesterDevicePlatformAttribute(): string
    {
        $platform = $this->attributes['requester_device_platform'] ?? null;
        return in_array($platform, ['Android', 'Windows', 'iOS', 'Mac', 'Linux'], true)
            ? $platform
            : DeviceNameResolver::platformFromUserAgent($this->requester_user_agent);
    }
}
