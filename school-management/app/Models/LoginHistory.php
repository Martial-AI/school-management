<?php

namespace App\Models;

use App\Services\DeviceNameResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    protected $fillable = ['user_id', 'ip_address', 'user_agent', 'device_model', 'device_platform', 'device_browser', 'logged_in_at', 'logged_out_at'];
    protected function casts(): array { return ['logged_in_at' => 'datetime', 'logged_out_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function getDeviceNameAttribute(): string
    {
        return $this->device_model ?: DeviceNameResolver::modelFromUserAgent($this->user_agent) ?: ($this->device_browser ?: DeviceNameResolver::fromUserAgent($this->user_agent));
    }
    public function getDevicePlatformAttribute(): string
    {
        $platform = $this->attributes['device_platform'] ?? null;
        return in_array($platform, ['Android', 'Windows', 'iOS', 'Mac', 'Linux'], true)
            ? $platform
            : DeviceNameResolver::platformFromUserAgent($this->user_agent);
    }
}
