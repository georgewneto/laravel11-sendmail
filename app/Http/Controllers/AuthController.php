<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Obter o usuário autenticado
    public function me()
    {
        //alterar para consultar a API de autenticação
        return response()->json(Auth::user());
    }

    // Logout do usuário
    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Logout realizado com sucesso!']);
    }

    // Refresh no token JWT
    public function refresh()
    {
        $newToken = Auth::refresh();
        return response()->json(['token' => $newToken]);
    }
}
