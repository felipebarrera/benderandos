<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acceso — {{ tenancy()->tenant->nombre ?? 'BenderAnd' }}</title>
    <link rel="stylesheet" href="/css/benderand.css">
</head>
<body>

<div class="auth-shell">
    <div class="auth-card">
        <div class="auth-logo">B<span>&amp;</span></div>
        <p class="auth-tagline">
            {{ tenancy()->tenant->nombre ?? 'BenderAnd POS' }}
            @if(tenancy()->tenant->rubro ?? null)
                &bull; <span class="text-accent">{{ tenancy()->tenant->rubro }}</span>
            @endif
        </p>

        @if($errors->any())
        <div class="card" style="border-color:var(--err); background:rgba(255,63,91,.07); margin-bottom:20px;">
            <p style="color:var(--err); font-size:13px;">{{ $errors->first() }}</p>
        </div>
        @endif

        @if(session('success'))
        <div class="card" style="border-color:var(--ok); background:rgba(0,229,160,.07); margin-bottom:20px;">
            <p style="color:var(--ok); font-size:13px;">{{ session('success') }}</p>
        </div>
        @endif

        <form action="{{ route('tenant.login.web.post') }}" method="POST" id="loginForm">
            @csrf
            <div class="field">
                <label class="label">RUT o Email</label>
                <input type="text" name="login" class="input-mono" placeholder="12.345.678-9" 
                    autofocus autocomplete="username" required value="{{ old('login') }}">
            </div>
            <div class="field">
                <label class="label">Contraseña</label>
                <div class="search-wrap">
                    <input type="password" name="password" id="pwdInput" placeholder="••••••••" 
                        autocomplete="current-password" required>
                    <button type="button" onclick="togglePwd()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--t2);cursor:pointer;">
                        <svg id="eyeIcon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px;">
                Entrar al sistema
            </button>
        </form>

        <div class="divider" style="margin-top:24px;"></div>
        <p style="text-align:center; font-size:12px; color:var(--t2);">
            ¿Primera vez? Regístrate por
            <span style="color:var(--accent); font-weight:600;">WhatsApp →</span>
        </p>
    </div>
</div>

<script>
function togglePwd() {
    const input = document.getElementById('pwdInput');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
