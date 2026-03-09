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

    // ========================================
    // ENDPOINTS NOVOS - Sistema Multi-SMTP
    // ========================================

    // Enfileirar nova mensagem (requer from_email)
    Route::post('store', [EmailController::class, 'store']);

    // Obter status e histórico de uma mensagem
    Route::get('messages/{id}/status', [EmailController::class, 'status']);

    // Obter estatísticas de todas as contas SMTP
    Route::get('smtp-accounts/stats', [EmailController::class, 'stats']);

    // ========================================
    // ENDPOINTS DEPRECADOS (manter por compatibilidade)
    // ========================================

    // @deprecated - Use POST /store com campo from_email
    // Processamento manual da fila antiga
    Route::get('envioemail', [EmailController::class, 'envioemail']);

    // @deprecated - Use POST /store com campo from_email
    // Envio direto sem fila
    Route::post('send-email', [EmailController::class, 'sendEmail']);

    // @deprecated - Use POST /store com campo from_email
    // Envio direto sem fila
    Route::post('send', [EmailController::class, 'send']);

    // ========================================
    // TESTE
    // ========================================

    //apenas teste para verificar se a autenticação está funcionando
    Route::get('dados', function () {
        return response()->json(['dados' => 'Informações protegidas da segunda API']);
    });
});
