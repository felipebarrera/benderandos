<!DOCTYPE html>
<html lang="es" style="background:#08080a">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Central - BenderAnd</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #08080a; --s1: #111115; --s2: #18181e;
            --b1: #1e1e28; --b2: #2a2a3a;
            --tx: #e8e8f0; --t2: #7878a0;
            --ac: #e040fb; /* Purple */
            --ok: #00e5a0; --err: #ff3f5b;
            --mono: 'IBM Plex Mono', monospace;
            --sans: 'IBM Plex Sans', sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--bg);
            color: var(--tx);
            font-family: var(--sans);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: var(--s1);
            border: 1px solid var(--b1);
            border-radius: 16px;
            padding: 40px;
        }
        .brand {
            font-family: var(--mono);
            font-weight: 700;
            font-size: 24px;
            color: var(--ac);
            text-align: center;
            margin-bottom: 8px;
        }
        .subtitle {
            text-align: center;
            color: var(--t2);
            font-size: 13px;
            margin-bottom: 32px;
        }
        .f { margin-bottom: 16px; }
        .f label {
            display: block;
            font-family: var(--mono);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--t2);
            margin-bottom: 6px;
        }
        .f input {
            width: 100%;
            background: var(--s2);
            border: 1px solid var(--b2);
            border-radius: 8px;
            padding: 12px;
            color: var(--tx);
            font-size: 14px;
            outline: none;
            transition: border-color .2s;
        }
        .f input:focus { border-color: var(--ac); }
        .btn-primary {
            width: 100%;
            background: var(--ac);
            color: #000;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: transform .1s;
            margin-top: 10px;
        }
        .btn-primary:active { transform: scale(0.98); }
        .error {
            background: rgba(255,63,91,.1);
            border: 1px solid rgba(255,63,91,.2);
            color: var(--err);
            padding: 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">BenderAnd</div>
        <div class="subtitle">Panel de Control Centralizado</div>

        @if($errors->any())
            <div class="error">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('central.login.post') }}" method="POST">
            @csrf
            <div class="f">
                <label>Email de Acceso</label>
                <input type="email" name="email" required placeholder="admin@benderand.cl">
            </div>

            <div class="f">
                <label>Contraseña</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-primary">Entrar al Sistema</button>
        </form>
    </div>
</body>
</html>
