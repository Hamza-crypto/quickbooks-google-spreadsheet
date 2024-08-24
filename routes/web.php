<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::controller(WebhookController::class)->group(function () {
    Route::get('webhook', 'webhook');
    Route::post('webhook', 'webhook');
});

Route::get('migrate', function () {
    Artisan::call('migrate');
    dump('Migration Done');
});

// Route::get('migrate/fresh', function () {
//     Artisan::call('migrate:fresh --seed');
//     dump('Migration Done');
// });

Route::get('refresh-access-token', function () {
    Artisan::call('quickbooks:refresh-access-token');
});


Route::get('optimize', function () {
    Artisan::call('optimize:clear');
    dump('Optimization Done');

});

Route::get('process-webhooks', function () {
    Artisan::call('quickbooks:process-webhooks');
});
