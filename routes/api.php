<?php

use App\Http\Controllers\Webhook\PortalyController;
use Illuminate\Support\Facades\Route;

// Portaly Webhook
Route::post('/webhooks/portaly', [PortalyController::class, 'handle'])
    ->name('webhook.portaly');
