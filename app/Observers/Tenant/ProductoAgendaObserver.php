<?php
namespace App\Observers\Tenant;

use App\Models\Tenant\Producto;
use App\Services\AgendaAutoRegistroService;

class ProductoAgendaObserver
{
    private AgendaAutoRegistroService $svc;

    public function __construct(AgendaAutoRegistroService $svc)
    {
        $this->svc = $svc;
    }

    public function created(Producto $producto): void
    {
        $this->svc->registrarProductoRenta($producto);
    }

    public function updated(Producto $producto): void
    {
        if ($producto->tipo_producto === 'renta' && $producto->estado === 'activo') {
            $this->svc->registrarProductoRenta($producto);
        } else {
            $this->svc->desactivarProductoRenta($producto->id);
        }
    }

    public function deleted(Producto $producto): void
    {
        $this->svc->desactivarProductoRenta($producto->id);
    }
}
