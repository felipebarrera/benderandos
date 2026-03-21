<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Cliente;
use App\Models\Tenant\Usuario;
use App\Models\Tenant\Producto;
use App\Models\Tenant\Venta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientePortalController extends Controller
{
    /**
     * Vistas Blade del Portal
     */
    public function showLogin()
    {
        return view('tenant.portal.auth.login', ['title' => 'Acceso Cliente']);
    }

    public function loginWeb(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        // Buscar cliente por email
        $usuario = Usuario::where('email', $request->login)
                          ->where('rol', 'cliente')
                          ->first();

        if (!$usuario || !Hash::check($request->password, $usuario->clave_hash)) {
            return back()->withErrors(['login' => 'Credenciales inválidas para el portal.']);
        }

        if (!$usuario->activo) {
            return back()->withErrors(['login' => 'Tu cuenta está desactivada.']);
        }

        auth()->login($usuario);
        
        // Sincronizar habilidades en la sesión si es necesario, 
        // pero para Web usamos Gate/Middlewares estándar preferentemente.
        
        return redirect()->route('portal.catalogo');
    }

    public function logoutWeb()
    {
        auth()->logout();
        return redirect()->route('portal.login');
    }

    public function index()
    {
        return view('tenant.portal.catalogo', ['title' => 'Catálogo de Productos']);
    }

    public function catalogoWeb()
    {
        $productos = Producto::activos()
                             ->conStock()
                             ->get();
                             
        return view('tenant.portal.catalogo', [
            'title' => 'Catálogo',
            'productos' => $productos
        ]);
    }

    public function historialWeb(Request $request)
    {
        $cliente = Cliente::where('usuario_id', $request->user()->id)->first();
        $ventas = Venta::with(['items.producto'])
                       ->where(fn($q) => $q->where('cliente_id', $cliente->id))
                       ->orderBy('created_at', 'desc')
                       ->get();

        return view('tenant.portal.historial', [
            'title' => 'Mis Pedidos',
            'ventas' => $ventas
        ]);
    }

    public function deudasWeb(Request $request)
    {
        $cliente = Cliente::where('usuario_id', $request->user()->id)->first();
        $deudas = $cliente->deudas()
                          ->pendientes()
                          ->get();

        return view('tenant.portal.deudas', [
            'title' => 'Estado de Cuenta',
            'deudas' => $deudas
        ]);
    }

    /**
     * POST /portal/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $usuario = Usuario::where('email', $request->email)
                          ->where('rol', 'cliente')
                          ->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->clave_hash)) {
            return response()->json(['message' => 'Credenciales de cliente inválidas'], 401);
        }

        if (! $usuario->activo) {
            return response()->json(['message' => 'Cuenta de cliente desactivada'], 403);
        }

        $cliente = Cliente::where('usuario_id', $usuario->id)->first();

        if (!$cliente) {
            return response()->json(['message' => 'Usuario no vinculado a un registro de cliente'], 404);
        }

        $usuario->update(['ultimo_login' => now()]);

        $token = $usuario->createToken('cliente-token', [
            'ver-historial',
            'crear-pedido',
            'ver-deudas',
            'ver-catalogo'
        ])->plainTextToken;

        return response()->json([
            'token'   => $token,
            'cliente' => $cliente,
            'usuario' => [
                'id'     => $usuario->id,
                'nombre' => $usuario->nombre,
                'email'  => $usuario->email,
            ]
        ]);
    }

    /**
     * GET /portal/catalogo
     */
    public function catalogo(): JsonResponse
    {
        $productos = Producto::activos()
                             ->conStock()
                             ->get(['id', 'nombre', 'descripcion', 'valor_venta', 'cantidad', 'tipo_producto']);

        return response()->json($productos);
    }

    /**
     * GET /portal/historial
     */
    public function historial(Request $request): JsonResponse
    {
        $cliente = Cliente::where('usuario_id', $request->user()->id)->first();

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        $ventas = Venta::with(['items.producto'])
                       ->where(fn($q) => $q->where('cliente_id', $cliente->id))
                       ->orderBy('created_at', 'desc')
                       ->get();

        return response()->json($ventas);
    }

    /**
     * POST /portal/pedido
     */
    public function crearPedido(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'tipo_entrega' => 'required|in:retiro,envio',
        ]);

        $cliente = Cliente::where('usuario_id', $request->user()->id)->first();

        $venta = Venta::create([
            'uuid' => (string) Str::uuid(),
            'cliente_id' => $cliente->id,
            'estado' => 'remota_pendiente',
            'origen' => 'web',
            'tipo_entrega' => $request->tipo_entrega,
            'total' => 0, // Se calculará al sumar items
        ]);

        $total = 0;
        foreach ($request->items as $itemData) {
            $producto = Producto::findOrFail($itemData['producto_id']);
            $cantidad = $itemData['cantidad'];
            
            $subtotal = $producto->valor_venta * $cantidad;
            $total += $subtotal;

            $venta->items()->create([
                'producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $producto->valor_venta,
                'subtotal' => $subtotal,
            ]);
        }

        $venta->update(['total' => $total]);

        // Aquí se podría disparar un Job para notificar vía WhatsApp
        // SendWhatsAppNotification::dispatch($venta, 'pedido_remoto_nuevo');

        return response()->json($venta->load('items.producto'), 201);
    }

    /**
     * GET /portal/deudas
     */
    public function deudas(Request $request): JsonResponse
    {
        $cliente = Cliente::where('usuario_id', $request->user()->id)->first();

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        $deudas = $cliente->deudas()
                          ->pendientes()
                          ->get();

        return response()->json($deudas);
    }
}
