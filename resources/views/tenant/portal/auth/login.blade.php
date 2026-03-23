<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Acceso Cliente' }} — Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;
             background:linear-gradient(135deg,#0f0c29,#302b63,#24243e)}
        .card{background:rgba(255,255,255,.06);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
              border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:40px 36px;width:100%;max-width:400px;
              box-shadow:0 25px 50px rgba(0,0,0,.3)}
        .header{text-align:center;margin-bottom:28px}
        .header .icon{font-size:32px;margin-bottom:8px}
        .header h1{font-size:22px;font-weight:700;color:#fff;margin:0 0 4px}
        .header p{font-size:13px;color:rgba(255,255,255,.5);margin:0}
        .error-box{background:rgba(255,63,91,.12);border:1px solid rgba(255,63,91,.25);border-radius:8px;
                   padding:10px 14px;margin-bottom:16px;color:#ff3f5b;font-size:12px}
        .field{margin-bottom:16px}
        .field label{display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,.6);margin-bottom:6px;
                     text-transform:uppercase;letter-spacing:1px}
        .field input{width:100%;padding:10px 14px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);
                     border-radius:8px;color:#fff;font-size:14px;outline:none;transition:border .2s}
        .field input:focus{border-color:rgba(224,64,251,.5)}
        .field input::placeholder{color:rgba(255,255,255,.25)}
        .btn{width:100%;padding:12px;background:linear-gradient(135deg,#e040fb,#7c4dff);border:none;border-radius:8px;
             color:#fff;font-size:14px;font-weight:600;cursor:pointer;transition:opacity .2s}
        .btn:hover{opacity:.9}
        .footer{text-align:center;margin-top:20px}
        .footer a{color:rgba(224,64,251,.7);font-size:12px;text-decoration:none}
        .footer a:hover{color:rgba(224,64,251,1)}
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <div class="icon">🛒</div>
        <h1>Portal de Clientes</h1>
        <p>Ingresa con tu cuenta para ver pedidos y catálogo</p>
    </div>

    @if($errors->any())
    <div class="error-box">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('portal.login.submit') }}">
        @csrf
        <div class="field">
            <label>Email</label>
            <input type="email" name="login" value="{{ old('login') }}" required autofocus placeholder="tu@email.com">
        </div>
        <div class="field" style="margin-bottom:24px">
            <label>Contraseña</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn">Iniciar Sesión</button>
    </form>

    <div class="footer">
        <a href="{{ route('public.portal.catalogo') }}">← Ver catálogo sin cuenta</a>
    </div>
</div>
</body>
</html>
