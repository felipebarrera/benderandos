<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\OrdenCompra;
use App\Models\Tenant\Proveedor;
use App\Models\Tenant\ProductoProveedor;
use App\Services\ComprasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrdenCompraController extends Controller
{
    public function __construct(
        private ComprasService $comprasService
    ) {}

    // =================== PROVEEDORES ===================

    public function proveedoresIndex(Request $request): JsonResponse
    {
        $query = Proveedor::query()->orderBy('nombre');
        if ($request->filled('q')) {
            $query->buscar($request->q);
        }
        if ($request->boolean('solo_activos', true)) {
            $query->activos();
        }
        return response()->json($query->paginate($request->input('per_page', 30)));
    }

    public function proveedorShow($id): JsonResponse
    {
        return response()->json(
            Proveedor::with('productosProveedor.producto')->findOrFail($id)
        );
    }

    public function proveedorStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'             => 'required|string|max:255',
            'rut'                => 'nullable|string|max:12',
            'razon_social'       => 'nullable|string|max:255',
            'giro'               => 'nullable|string|max:255',
            'direccion'          => 'nullable|string|max:255',
            'comuna'             => 'nullable|string|max:100',
            'ciudad'             => 'nullable|string|max:100',
            'telefono'           => 'nullable|string|max:20',
            'email'              => 'nullable|email',
            'contacto_nombre'    => 'nullable|string|max:255',
            'contacto_telefono'  => 'nullable|string|max:20',
            'plazo_pago_dias'    => 'nullable|integer|min:0',
            'descuento_volumen_pct' => 'nullable|numeric|min:0|max:100',
            'monto_minimo_oc'    => 'nullable|integer|min:0',
            'notas'              => 'nullable|string',
        ]);

        $prov = Proveedor::create($data);
        return response()->json($prov, 201);
    }

    public function proveedorUpdate(Request $request, int $id): JsonResponse
    {
        $prov = Proveedor::findOrFail($id);
        $prov->update($request->only([
            'nombre', 'rut', 'razon_social', 'giro', 'direccion', 'comuna', 'ciudad',
            'telefono', 'email', 'contacto_nombre', 'contacto_telefono',
            'plazo_pago_dias', 'descuento_volumen_pct', 'monto_minimo_oc', 'notas', 'activo',
        ]));
        return response()->json($prov->fresh());
    }

    public function vincularProducto(Request $request, int $proveedorId): JsonResponse
    {
        $data = $request->validate([
            'producto_id'          => 'required|integer|exists:productos,id',
            'precio_unitario'      => 'required|integer|min:0',
            'codigo_proveedor'     => 'nullable|string|max:50',
            'cantidad_minima_pedido' => 'nullable|integer|min:1',
            'dias_entrega'         => 'nullable|integer|min:0',
            'es_principal'         => 'nullable|boolean',
        ]);
        $data['proveedor_id'] = $proveedorId;

        $pp = ProductoProveedor::updateOrCreate(
            ['proveedor_id' => $proveedorId, 'producto_id' => $data['producto_id']],
            $data
        );
        return response()->json($pp, 201);
    }

    // =================== ORDENES DE COMPRA ===================

    public function ocIndex(Request $request): JsonResponse
    {
        $query = OrdenCompra::with('proveedor', 'usuario')->orderByDesc('created_at');
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        return response()->json($query->paginate($request->input('per_page', 25)));
    }

    public function ocShow($id): JsonResponse
    {
        return response()->json(
            OrdenCompra::with(['proveedor', 'items.producto', 'recepciones.items.producto', 'usuario'])->findOrFail($id)
        );
    }

    public function ocStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'proveedor_id'          => 'required|integer|exists:proveedores,id',
            'fecha_entrega_esperada' => 'nullable|date',
            'notas'                 => 'nullable|string',
            'descuento_pct'         => 'nullable|numeric|min:0|max:100',
            'items'                 => 'required|array|min:1',
            'items.*.producto_id'   => 'required|integer|exists:productos,id',
            'items.*.cantidad'      => 'required|numeric|min:0.001',
            'items.*.precio_unitario' => 'required|integer|min:0',
        ]);

        $oc = $this->comprasService->crearOrden($data, $request->user());
        return response()->json($oc, 201);
    }

    public function ocAutorizar(Request $request, int $id): JsonResponse
    {
        $oc = OrdenCompra::findOrFail($id);
        try {
            $oc = $this->comprasService->autorizar($oc, $request->user());
            return response()->json(['message' => "OC {$oc->codigo} autorizada", 'oc' => $oc]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function ocEnviar($id): JsonResponse
    {
        $oc = OrdenCompra::findOrFail($id);
        try {
            $oc = $this->comprasService->enviar($oc);
            return response()->json(['message' => "OC {$oc->codigo} enviada", 'oc' => $oc]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function ocAnular($id): JsonResponse
    {
        $oc = OrdenCompra::findOrFail($id);
        $oc->update(['estado' => 'anulada']);
        return response()->json(['message' => "OC {$oc->codigo} anulada"]);
    }

    // =================== RECEPCIONES ===================

    public function registrarRecepcion(Request $request, int $ocId): JsonResponse
    {
        $oc = OrdenCompra::findOrFail($ocId);

        $data = $request->validate([
            'numero_guia'                   => 'nullable|string|max:100',
            'observaciones'                 => 'nullable|string',
            'items'                         => 'required|array|min:1',
            'items.*.item_orden_id'         => 'required|integer|exists:items_orden_compra,id',
            'items.*.cantidad_recibida'     => 'required|numeric|min:0',
            'items.*.cantidad_rechazada'    => 'nullable|numeric|min:0',
            'items.*.motivo_rechazo'        => 'nullable|string|max:255',
            'items.*.lote'                  => 'nullable|string|max:50',
            'items.*.fecha_vencimiento'     => 'nullable|date',
        ]);

        try {
            $recepcion = $this->comprasService->registrarRecepcion($oc, $data, $request->user());
            return response()->json(['message' => 'Recepción registrada', 'recepcion' => $recepcion], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // =================== DASHBOARD & ALERTAS ===================

    public function dashboard(): JsonResponse
    {
        $dashboard = $this->comprasService->getDashboard();
        $ultimasOc = OrdenCompra::with('proveedor')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'kpis'       => $dashboard,
            'ultimas_oc' => $ultimasOc,
        ]);
    }

    public function alertasStock(): JsonResponse
    {
        return response()->json($this->comprasService->detectarStockBajo());
    }
}
