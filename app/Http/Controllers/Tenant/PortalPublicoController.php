<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Producto;
use App\Models\Tenant\RubroConfig;
use Illuminate\Http\Request;
use App\Http\Controllers\Tenant\AgendaController;

class PortalPublicoController extends Controller
{
    /**
     * Landing page del portal público.
     */
    public function index()
    {
        $config = RubroConfig::first();

        // Si la industria del tenant es salud, delegamos la página de inicio al landing médico.
        if ($config && in_array($config->industria_preset, ['medico', 'clinica', 'profesional'])) {
            return app(AgendaController::class)->landing(request());
        }

        $tenant = tenant();
        $productos = Producto::where('estado', 'activo')
            ->where('visible_en_portal', true)
            ->limit(12)
            ->get();

        return view('tenant.portal.index', compact('config', 'tenant', 'productos'));
    }

    /**
     * Catálogo completo de productos.
     */
    public function catalogo()
    {
        $config = RubroConfig::first();
        $productos = Producto::where('estado', 'activo')
            ->where('visible_en_portal', true)
            ->paginate(24);

        return view('tenant.portal.catalogo', compact('config', 'productos'));
    }

    /**
     * Detalle de un producto específico.
     */
    public function producto($id)
    {
        $config = RubroConfig::first();
        $producto = Producto::findOrFail($id);

        if (!$producto->visible_en_portal || $producto->estado !== 'activo') {
            abort(404);
        }

        return view('tenant.portal.producto', compact('config', 'producto'));
    }

    /**
     * Redirección dinámica a WhatsApp con mensaje pre-construido.
     */
    public function pedirPorWhatsapp(Request $request)
    {
        $config = RubroConfig::first();
        $numero = $config->portal_whatsapp_numero ?? tenant()->whatsapp_admin;
        
        if (!$numero) {
            return back()->with('error', 'El número de WhatsApp no está configurado.');
        }

        $mensaje = $request->get('mensaje', 'Hola, vengo desde tu portal web y me gustaría hacer una consulta.');
        
        $url = 'https://wa.me/' . preg_replace('/\D/', '', $numero)
             . '?text=' . urlencode($mensaje);

        return redirect($url);
    }
}
