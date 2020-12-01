<?php

use App\Http\Controllers\AccessLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [AccessLogController::class, 'index']);

Route::post('log', [AccessLogController::class, 'store']);

Route::delete('log/{name}', [AccessLogController::class, 'destroy']);

Route::get('log/{name}', [AccessLogController::class, 'download']);

Route::get('aggregate/{byCondition}/{name?}', [AccessLogController::class, 'aggregateBy']);

Route::fallback(function() {
    return response()->json([
        'error_msg' => 'Invalid resource'
    ], 404);
});