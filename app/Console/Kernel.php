<?php

namespace App\Console;

use App\Jobs\FetchCurrenciesJob;
use App\Models\JobMetadata;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * Запланирование выполнение задачи FetchCurrenciesJob на каждый день в 00:00.
     * Это гарантирует, что данные о курсах валют будут автоматически обновляться каждую ночь.
     * Написана для демонстрации подхода к обновлению данных
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new FetchCurrenciesJob())->dailyAt('00:00');
    }

    /**
     * Регистрация команд приложения.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
