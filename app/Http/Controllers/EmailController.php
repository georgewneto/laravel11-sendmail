<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificationEmail;
use App\Mail\SendEmail;
use App\Models\Mensagem;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmailController extends Controller
{
    public function send(Request $request)
    {
        // Validação dos dados de entrada
        $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        // Preparando os dados para o e-mail
        $emailData = [
            'subject' => $request->input('subject'),
            'message' => $request->input('message')
        ];

        // Enviando o e-mail
        Mail::to($request->input('email'))->send(new NotificationEmail($emailData));
        return response()->json(['message' => 'E-mail enviado com sucesso!'], 200);
    }

    public function sendEmail(Request $request)
    {
        // Validação dos dados de entrada
        $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);
        // Preparando os dados para o e-mail
        $emailData = [
            'subject' => $request->input('subject'),
            'message' => $request->input('message')
        ];

        // Enviando o e-mail
        Mail::to($request->input('email'))->send(new SendEmail($emailData));
        return response()->json(['message' => 'E-mail enviado com sucesso!'], 200);
    }

    function envioemail()
    {
        $user = JWTAuth::parseToken()->authenticate();
        /*
        if ($user->can('edit articles')) {
            // Usuário tem permissão para editar artigos
        }
        */
        if ($user->hasRole('admin')) {
            // Usuário possui o papel de administrador
            print('sou admin');
        }
        print($user);

        $mensagens = Mensagem::orderBy('data', 'ASC')
            ->where('situacao', 0)
            ->get();
        $i = 0;
        if (count($mensagens) > 0) {
            foreach ($mensagens as $mensagem) :

                if ($i++ > 2) break;
                //Mail::send(new Mensagem($mensagem));
                $emailData = [
                    'subject' => $mensagem->assunto,
                    'message' => $mensagem->corpo
                ];
                Mail::to($mensagem->email_destino)->send(new SendEmail($emailData));

                $mail = Mensagem::find($mensagem->id);
                $mail->situacao = 1;
                $mail->save();
            endforeach;
        }
        return response()->json(['message' => $i .' e-mail(s) enviado(s)'], 200);
    }

    function store(Request $request)
    {
        try {
            $request->validate([
                'email_destino' => 'required|email',
                'assunto' => 'required|string',
                'corpo' => 'required|string'
            ]);
            try {
                $mail = new Mensagem();
                $mail->email_destino = $request->email_destino;
                $mail->assunto = $request->assunto;
                $mail->corpo = $request->corpo;
                $mail->data = date("Y-m-d");
                $mail->hora = date("H:i:s");
                $mail->situacao = 0;
                $mail->save();
                return response()->json(['message' => 'Mensagem salva com sucesso!'], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
