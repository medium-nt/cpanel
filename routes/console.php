<?php

use App\Services\MarketplaceApiService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    MarketplaceApiService::uploadingNewProducts();
    MarketplaceApiService::uploadingCancelledProducts();
})->everyFiveMinutes();
