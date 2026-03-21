<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class WebPanelController extends Controller
{
    /* ── Auth Web ──────────────────────────────────────────── */

    public function showLogin(): \Illuminate\View\View
    {
        return view('tenant.auth.login');
    }

    public function postLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $usuario = Usuario::where('email', $request->login)
            ->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->clave_hash)) {
            return back()->withErrors(['login' => 'Credenciales inválidas.'])->withInput();
        }

        if (! $usuario->activo) {
            return back()->withErrors(['login' => 'Cuenta desactivada.'])->withInput();
        }

        $usuario->update(['ultimo_login' => now()]);
        
        // Iniciamos sesión con el guard por defecto
        auth()->login($usuario);

        // Redirigir según rol
        return match ($usuario->rol) {
            'super_admin', 'admin' => redirect('/admin/dashboard'),
            'cajero'               => redirect('/pos'),
            'operario', 'bodega'   => redirect('/operario'),
            default                => redirect('/pos'),
        };
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('tenant.login.web');
    }

    /* ── Admin Views ───────────────────────────────────────── */

    public function dashboard(): \Illuminate\View\View
    {
        return view('tenant.admin.dashboard');
    }

    public function productos(): \Illuminate\View\View
    {
        return view('tenant.admin.productos');
    }

    public function compras(): \Illuminate\View\View
    {
        return view('tenant.admin.compras');
    }

    public function clientes(): \Illuminate\View\View
    {
        return view('tenant.admin.clientes');
    }

    public function usuarios(): \Illuminate\View\View
    {
        return view('tenant.admin.usuarios');
    }

    public function reportes(): \Illuminate\View\View
    {
        return view('tenant.admin.reportes');
    }

    public function rentas(): \Illuminate\View\View
    {
        return view('tenant.admin.rentas');
    }

    public function config(): \Illuminate\View\View
    {
        $tenant = tenancy()->tenant;
        return view('tenant.admin.config', compact('tenant'));
    }

    /* ── POS Views ─────────────────────────────────────────── */

    public function pos(): \Illuminate\View\View
    {
        /** @var \Illuminate\Support\Collection<int, Producto> $productos */
        $productos = Producto::activos()->get();
        $familias = $productos->pluck('familia')->filter()->unique()->values();

        return view('tenant.pos.index', compact('productos', 'familias'));
    }

    public function posHistorial(): \Illuminate\View\View
    {
        return view('tenant.pos.historial');
    }

    /* ── Operario Views ────────────────────────────────────── */

    public function operario(): \Illuminate\View\View
    {
        /** @var \Illuminate\Support\Collection<int, Producto> $productos */
        $productos = Producto::activos()->get();
        $familias = $productos->pluck('familia')->filter()->unique()->values();

        return view('tenant.operario.index', compact('productos', 'familias'));
    }
}
