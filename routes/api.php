<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/
use App\Http\Controllers\AuthController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\EmailController;

//Route::post('register', [AuthController::class, 'register']);
//Route::post('login', [AuthController::class, 'login']);

Route::middleware([JwtMiddleware::class])->group(function () {
    //Route::post('logout', [AuthController::class, 'logout']);
    //Route::get('me', [AuthController::class, 'me']);
    //Route::post('refresh', [AuthController::class, 'refresh']);

    //endpoint que será chamado pra enviar a fila
    Route::get('envioemail', [EmailController::class, 'envioemail']);

    Route::post('store', [EmailController::class, 'store']);
    Route::post('send-email', [EmailController::class, 'sendEmail']);
    Route::post('send', [EmailController::class, 'send']);
    Route::get('dados', function () {
        return response()->json(['dados' => 'Informações protegidas da segunda API']);
    });
});
