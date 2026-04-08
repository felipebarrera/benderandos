<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Usuario;
use App\Models\Tenant\RubroConfig;
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
        $config = RubroConfig::first();
        return view('tenant.admin.config', compact('tenant', 'config'));
    }

    public function recepcionDirectaIndex()
    {
        return view('tenant.admin.recepcion-directa');
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

    public function operario()
    {
        $usuario = auth()->user();

        // Si M08 activo y usuario tiene recurso de agenda → vista profesional
        $rubroConfig = RubroConfig::first();
        $tieneM08    = in_array('M08', $rubroConfig?->modulos_activos ?? []);

        if ($tieneM08) {
            $recurso = \App\Models\Tenant\AgendaRecurso::where('usuario_id', $usuario->id)
                ->where('activo', true)
                ->first();

            if ($recurso) {
                return redirect('/profesional');
            }
        }

        /** @var \Illuminate\Support\Collection<int, Producto> $productos */
        $productos = Producto::activos()->get();
        $familias = $productos->pluck('familia')->filter()->unique()->values();

        return view('tenant.operario.index', compact('productos', 'familias'));
    }

    /**
     * Vista profesional: médico, dentista, psicólogo, abogado, técnico con M08.
     * Muestra agenda personal + pacientes + historial + seguimiento.
     */
    public function profesional()
    {
        $usuario = auth()->user();

        // Obtener o crear recurso automáticamente
        $recurso = \App\Models\Tenant\AgendaRecurso::with(['servicios','horarios'])
            ->where('usuario_id', $usuario->id)
            ->where('activo', true)
            ->first();

        if (!$recurso) {
            $usuarioModel = \App\Models\Tenant\Usuario::find($usuario->id);
            if ($usuarioModel) {
                $recurso = app(\App\Services\AgendaAutoRegistroService::class)->registrarOperario($usuarioModel);
                if ($recurso) $recurso->load(['servicios','horarios']);
            }
        }

        if (!$recurso) {
            return redirect('/operario')->with('info', 'Tu usuario no tiene agenda configurada. Pide al administrador que te vincule.');
        }

        $rubroConfig   = \App\Models\Tenant\RubroConfig::first();
        $labelCliente  = $rubroConfig?->label_cliente ?? 'Paciente';
        $labelOperario = $rubroConfig?->label_operario ?? 'Profesional';

        return view('tenant.profesional.index', compact(
            'recurso', 'usuario', 'rubroConfig', 'labelCliente', 'labelOperario'
        ));
    }

    public function recepcionIndex()
    {
        $usuario = auth()->user();
        if (!in_array($usuario->rol, ['admin', 'super_admin', 'cajero', 'recepcionista'])) {
            abort(403, 'No tienes permiso para ver la recepción.');
        }

        $profesionales = \App\Models\Tenant\AgendaRecurso::where('activo', true)
            ->whereNotNull('usuario_id')
            ->select('id', 'nombre', 'color_hex as color')
            ->get()
            ->toArray();

        return view('tenant.recepcion.agenda', compact('profesionales'));
    }
}
