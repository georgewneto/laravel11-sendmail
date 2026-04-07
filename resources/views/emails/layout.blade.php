<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <style>
        /* Estilos básicos para o email */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            text-align: center;
            padding: 10px;
            background-color: #fff;
            color: #ffffff;
            font-size: 24px;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            color: #333333;
            line-height: 1.6;
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #777777;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
<<<<<<< HEAD
        <div class="header">
            <img src="https://processoseletivo.itabuna.ba.gov.br/images/logomarca_amarelo_preto_horizontal.png" width="300" border="0" /><br />
=======
        <div class="header" style="line-height: 2;">
            <img src="https://publico.tributositabuna.com.br/files/images/logo_031.svg" width="300" border="0" /><br />
>>>>>>> 255294e745c75e8f77fdb4cff46c0a5f368ea878
            @yield('header', 'Título do Email')
        </div>

        <div class="content">
            @yield('content')
        </div>

        <div class="footer" style="line-height: 2;">
            <!--
            yield('footer', '© ' . date('Y') . ' Prefeitura Municipal de Itabuna<br />Desenvolvido pelo <b>DIT</b> | Departamento de Inovação e Tecnologia')
            -->
            <p>© {{date('Y')}} Prefeitura Municipal de Itabuna<br />Desenvolvido pelo <b>DIT</b> | Departamento de Inovação e Tecnologia</p>
        </div>
    </div>
</body>
</html>
