<?php

use App\Models\Setting;
use App\Services\MarketplaceApiService;
use App\Services\MarketplaceSupplyService;
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

$workingDayStart = Setting::query()->where('name', 'working_day_start')->first()?->value;
Schedule::call(function () {
    UserService::sendMessageForWorkingTodayEmployees();
})->dailyAt($workingDayStart);

Schedule::call(function () {
    MarketplaceSupplyService::deleteOldVideos();
})->dailyAt('01:00');
