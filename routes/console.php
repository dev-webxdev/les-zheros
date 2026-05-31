<?php

use App\Support\MissionCycle;
use App\Support\SiteBackupManager;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('missions:sync-cycle', function () {
    app(MissionCycle::class)->sync();

    $this->info('Cycle des missions synchronise.');
})->purpose('Avance automatiquement la date de fin des missions si elle est depassee');

Artisan::command('site:backup', function () {
    $path = app(SiteBackupManager::class)->create();

    $this->info('Sauvegarde creee : '.basename($path));
})->purpose('Cree une sauvegarde du site et conserve les 10 dernieres');

Schedule::command('site:backup')->dailyAt('03:00');
