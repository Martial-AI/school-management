<?php

namespace App\Services;

class DeviceNameResolver
{
    public static function modelFromUserAgent(?string $userAgent): ?string
    {
        $agent = $userAgent ?? '';
        if (! str_contains(strtolower($agent), 'android')) return null;

        // Android user agents commonly contain the manufacturer model just before "Build/".
        if (preg_match('/;\s*([^;()]+?)\s+(?:Build|BUILD)\//', $agent, $matches)) {
            $model = trim($matches[1]);
            if (! in_array(strtolower($model), ['wv', 'en-us', 'fr-fr'], true)) return $model;
        }

        return null;
    }

    public static function platformFromUserAgent(?string $userAgent): string
    {
        $agent = strtolower($userAgent ?? '');
        return match (true) {
            str_contains($agent, 'iphone'), str_contains($agent, 'ipad') => 'iOS',
            str_contains($agent, 'android') => 'Android',
            str_contains($agent, 'windows') => 'Windows',
            str_contains($agent, 'macintosh'), str_contains($agent, 'mac os') => 'Mac',
            str_contains($agent, 'linux') => 'Linux',
            default => 'Appareil inconnu',
        };
    }

    public static function fromUserAgent(?string $userAgent): string
    {
        $agent = strtolower($userAgent ?? '');
        if ($model = self::modelFromUserAgent($userAgent)) return $model;

        $browser = match (true) {
            str_contains($agent, 'samsungbrowser/') => 'Samsung Internet',
            str_contains($agent, 'edg/') => 'Microsoft Edge',
            str_contains($agent, 'opr/'), str_contains($agent, 'opera') => 'Opera',
            str_contains($agent, 'firefox/') => 'Firefox',
            str_contains($agent, 'chrome/'), str_contains($agent, 'crios/') => 'Google Chrome',
            str_contains($agent, 'safari/') => 'Safari',
            default => 'Navigateur',
        };

        $platform = self::platformFromUserAgent($userAgent);

        return $browser.' sur '.$platform;
    }

    public static function browserFromUserAgent(?string $userAgent): string
    {
        $label = self::fromUserAgent($userAgent);
        return str_replace(' sur '.self::platformFromUserAgent($userAgent), '', $label);
    }
}
