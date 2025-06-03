<?php

namespace App\Providers;

use App\Console\Commands\PlayScheduledPlaylists;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(Schedule $schedule): void
    {
      $schedule->command('run:scheduled-playlists')
        ->everyFiveSeconds()
        ->withoutOverlapping();

    }
}
