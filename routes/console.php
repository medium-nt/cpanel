<?php

use App\Models\Setting;
use App\Services\MarketplaceApiService;
use App\Services\MarketplaceSupplyService;
use App\Services\TransactionService;
use App\Services\UserService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    MarketplaceApiService::uploadingNewProducts();
    MarketplaceApiService::uploadingCancelledProducts();
})->everyFiveMinutes();

$workingDayStart = app()->runningUnitTests()
    ? '09:00'
    : Setting::query()->where('name', 'working_day_start')->first()?->value;

Schedule::call(function () {
    UserService::sendMessageForWorkingTodayEmployees();
})->dailyAt($workingDayStart);

Schedule::call(function () {
    UserService::checkUnclosedWorkShifts();
    UserService::clearTimeForClosedWorkShifts();
})->dailyAt('00:01');

Schedule::call(function () {
    TransactionService::activateHoldBonus();
})->dailyAt('00:30');

Schedule::call(function () {
    TransactionService::accrualStorekeeperSalary();
})->dailyAt('00:30');

Schedule::call(function () {
    TransactionService::accrualSeamstressesSalary();
})->dailyAt('00:35');

Schedule::call(function () {
    TransactionService::accrualCuttersSalary();
})->dailyAt('00:45');

Schedule::call(function () {
    MarketplaceSupplyService::deleteOldVideos();
})->dailyAt('01:00');

Schedule::command('queue:work --stop-when-empty')
    ->everyMinute()->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/queue.log'));


