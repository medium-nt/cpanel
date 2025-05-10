<?php

use App\Services\MarketplaceApiService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    echo ' Starting schedule';
    Log::channel('marketplace_api')->info('Тест крона.');
    MarketplaceApiService::uploadingNewProducts();
})->everyFiveMinutes();
