<?php

namespace App\Providers;

use App\Models\LoginHistory;
use App\Services\DeviceNameResolver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            LoginHistory::create([
                'user_id' => $event->user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'device_model' => request()->input('device_model'),
                'device_platform' => DeviceNameResolver::platformFromUserAgent(request()->userAgent()),
                'device_browser' => request()->input('device_browser') ?: DeviceNameResolver::browserFromUserAgent(request()->userAgent()),
                'logged_in_at' => now(),
            ]);
            activity('connexion')->causedBy($event->user)->performedOn($event->user)->log('s’est connecté');
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user) {
                if (request()->hasSession()) DB::table('active_sessions')->where('session_id', request()->session()->getId())->update(['ended_at' => now(), 'updated_at' => now()]);
                LoginHistory::where('user_id', $event->user->id)->whereNull('logged_out_at')->latest('id')->first()?->update(['logged_out_at' => now()]);
                activity('connexion')->causedBy($event->user)->performedOn($event->user)->log('s’est déconnecté');
            }
        });
    }
}
