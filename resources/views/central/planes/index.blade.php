@extends('layouts.central')

@section('title', 'Gestión de Planes')

@section('content')
<div class="page-hdr">
    <div class="page-title">Planes del Sistema</div>
    <div class="fr">
        <button class="btn btn-primary" onclick="openPlanModal()">+ Nuevo Plan</button>
    </div>
</div>

<div class="card" style="padding:0">
    <table class="tbl">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Precio (CLP)</th>
                <th>Usuarios</th>
                <th>Productos</th>
                <th style="text-align:right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($planes as $plan)
            <tr>
                <td style="font-weight:600">{{ $plan->nombre }}</td>
                <td style="font-family:var(--mono)">${{ number_format($plan->precio_mensual_clp, 0, ',', '.') }}</td>
                <td>{{ $plan->max_usuarios }}</td>
                <td>{{ $plan->max_productos }}</td>
                <td style="text-align:right">
                    <button class="btn btn-secondary btn-sm" onclick="editPlan({{ json_encode($plan) }})">✏️</button>
                    <form action="{{ route('central.billing.plan.destroy', $plan->id) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este plan?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal Plan -->
<div id="planModal" class="modal-overlay" style="display:none">
    <div class="modal card" style="max-width:500px">
        <div class="modal-hdr">
            <div class="modal-title" id="modalTitle">Nuevo Plan</div>
            <button class="btn-close" onclick="closePlanModal()">✕</button>
        </div>
        <form id="planForm" method="POST">
            @csrf
            <div id="methodField"></div>
            <div class="field">
                <label>Nombre del Plan</label>
                <input type="text" name="nombre" id="planNombre" required class="input">
            </div>
            <div class="field">
                <label>Precio Mensual (CLP)</label>
                <input type="number" name="precio_mensual_clp" id="planPrecio" required class="input">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
                <div class="field">
                    <label>Máx. Usuarios</label>
                    <input type="number" name="max_usuarios" id="planUsers" required class="input">
                </div>
                <div class="field">
                    <label>Máx. Productos</label>
                    <input type="number" name="max_productos" id="planProds" required class="input">
                </div>
            </div>
            <div class="modal-ftr" style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px">
                <button type="button" class="btn" onclick="closePlanModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSubmit">Guradar Plan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPlanModal() {
    document.getElementById('modalTitle').innerText = 'Nuevo Plan';
    document.getElementById('planForm').action = "{{ route('central.billing.plan.store') }}";
    document.getElementById('methodField').innerHTML = "";
    document.getElementById('planForm').reset();
    document.getElementById('planModal').style.display = 'flex';
}

function closePlanModal() {
    document.getElementById('planModal').style.display = 'none';
}

function editPlan(plan) {
    document.getElementById('modalTitle').innerText = 'Editar Plan';
    document.getElementById('planForm').action = `/central/billing/planes/${plan.id}`;
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    
    document.getElementById('planNombre').value = plan.nombre;
    document.getElementById('planPrecio').value = plan.precio_mensual_clp;
    document.getElementById('planUsers').value = plan.max_usuarios;
    document.getElementById('planProds').value = plan.max_productos;
    
    document.getElementById('planModal').style.display = 'flex';
}
</script>

<style>
.modal-overlay {
    position: fixed; top:0; left:0; right:0; bottom:0;
    background: rgba(0,0,0,.5);
    display: flex; align-items: center; justify-content: center;
    z-index: 1000;
}
.modal { background: var(--s1); width: 100%; border-radius: 12px; padding: 24px; box-shadow: 0 10px 40px rgba(0,0,0,.2); }
.modal-hdr { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.modal-title { font-size: 18px; font-weight: 700; color: var(--t1); }
.btn-close { background: none; border: none; font-size: 20px; color: var(--t3); cursor: pointer; }
.field { margin-bottom: 16px; }
.field label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--t2); }
.input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--b2); background: var(--s2); color: var(--t1); font-size: 14px; }
</style>
@endsection
