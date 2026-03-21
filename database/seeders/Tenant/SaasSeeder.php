<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\SaasPlan;
use Illuminate\Database\Seeder;

class SaasSeeder extends Seeder
{
    public function run(): void
    {
        // Crear Planes
        $basico = SaasPlan::create([
            'codigo'            => 'basico',
            'nombre'            => 'Básico',
            'descripcion'       => 'Ideal para negocios pequeños y emprendedores.',
            'precio_mensual'    => 39000,
            'precio_anual'      => 390000,
            'max_usuarios'      => 3,
            'max_productos'     => 1000,
            'modulos_incluidos' => ['M01', 'M03', 'M07', 'M20'],
            'modulos_addon'     => ['M16', 'M22'],
            'soporte_nivel'     => 'email',
            'activo'            => true,
        ]);

        $pro = SaasPlan::create([
            'codigo'            => 'pro',
            'nombre'            => 'Pro',
            'descripcion'       => 'Para pymes en crecimiento con necesidades de gestión avanzada.',
            'precio_mensual'    => 89000,
            'precio_anual'      => 890000,
            'max_usuarios'      => 10,
            'max_productos'     => 5000,
            'modulos_incluidos' => ['M01', 'M03', 'M07', 'M20', 'M08', 'M13', 'M17', 'M21', 'M24'],
            'modulos_addon'     => ['M16', 'M22', 'M27'],
            'soporte_nivel'     => 'chat',
            'activo'            => true,
        ]);

        $enterprise = SaasPlan::create([
            'codigo'            => 'enterprise',
            'nombre'            => 'Enterprise',
            'descripcion'       => 'Control total, facturación sin límites y métricas detalladas para corporativos.',
            'precio_mensual'    => 189000,
            'precio_anual'      => 1890000,
            'max_usuarios'      => 999, // Ilimitado en framework limits rules
            'max_productos'     => 0,   // Ilimitado
            'modulos_incluidos' => ['M01', 'M03', 'M07', 'M20', 'M08', 'M13', 'M17', 'M21', 'M24', 'M22', 'M27', 'M23', 'M31'],
            'modulos_addon'     => [],
            'soporte_nivel'     => 'dedicado',
            'activo'            => true,
        ]);
        
        // El tenant inicial (que es BenderAnd operando BenderAnd)
        // se podría auto-insertar como el primer cliente SaaS
        // en modo Enterprise con plan_id = $enterprise->id
    }
}
