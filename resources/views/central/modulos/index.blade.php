@extends('layouts.central')

@section('title', 'Gestión de Módulos')

@section('content')
<div class="page-hdr">
    <div class="page-title">Módulos del Ecosistema</div>
    <div class="fr">
        <button class="btn btn-primary" onclick="openModuloModal()">+ Nuevo Módulo</button>
    </div>
</div>

<div class="card" style="padding:0">
    <table class="tbl">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio (CLP)</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th style="text-align:right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($modulos as $mod)
            <tr>
                <td style="font-family:var(--mono); font-size:12px; color:var(--t3)">{{ $mod->modulo_id }}</td>
                <td style="font-weight:600">{{ $mod->nombre }}</td>
                <td style="font-family:var(--mono)">${{ number_format($mod->precio_mensual, 0, ',', '.') }}</td>
                <td>
                    @if($mod->es_base)
                        <span class="badge" style="color:var(--info); background:rgba(0,184,212,.1)">BASE</span>
                    @else
                        <span class="badge" style="color:var(--t2); background:var(--s2)">ADICIONAL</span>
                    @endif
                </td>
                <td>
                    @if($mod->activo)
                        <span class="badge" style="color:var(--ok); background:rgba(0,229,160,.1)">ACTIVO</span>
                    @else
                        <span class="badge" style="color:var(--err); background:rgba(255,63,91,.1)">INACTIVO</span>
                    @endif
                </td>
                <td style="text-align:right">
                    <button class="btn btn-secondary btn-sm" onclick="editModulo({{ json_encode($mod) }})">✏️</button>
                    <form action="{{ route('central.billing.modulo.destroy', $mod->modulo_id) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este módulo?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal Modulo -->
<div id="moduloModal" class="modal-overlay" style="display:none">
    <div class="modal card" style="max-width:500px">
        <div class="modal-hdr">
            <div class="modal-title" id="mModalTitle">Nuevo Módulo</div>
            <button class="btn-close" onclick="closeModuloModal()">✕</button>
        </div>
        <form id="moduloForm" method="POST">
            @csrf
            <div id="mMethodField"></div>
            
            <div class="field" id="idField">
                <label>ID del Módulo (slug)</label>
                <input type="text" name="modulo_id" id="mId" required class="input" placeholder="ej: rrhh_premium">
            </div>

            <div class="field">
                <label>Nombre</label>
                <input type="text" name="nombre" id="mNombre" required class="input">
            </div>

            <div class="field">
                <label>Descripción</label>
                <textarea name="descripcion" id="mDesc" class="input" style="height:80px"></textarea>
            </div>

            <div class="field">
                <label>Precio Mensual (CLP)</label>
                <input type="number" name="precio_mensual" id="mPrecio" required class="input">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
                <div class="field">
                    <label style="display:flex; align-items:center; gap:8px">
                        <input type="checkbox" name="es_base" id="mEsBase" value="1"> Es Módulo Base
                    </label>
                </div>
                <div class="field">
                    <label style="display:flex; align-items:center; gap:8px">
                        <input type="checkbox" name="activo" id="mActivo" value="1" checked> Activo
                    </label>
                </div>
            </div>

            <div class="modal-ftr" style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px">
                <button type="button" class="btn" onclick="closeModuloModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Módulo</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModuloModal() {
    document.getElementById('mModalTitle').innerText = 'Nuevo Módulo';
    document.getElementById('moduloForm').action = "{{ route('central.billing.modulo.store') }}";
    document.getElementById('mMethodField').innerHTML = "";
    document.getElementById('idField').style.display = 'block';
    document.getElementById('moduloForm').reset();
    document.getElementById('mActivo').checked = true;
    document.getElementById('moduloModal').style.display = 'flex';
}

function closeModuloModal() {
    document.getElementById('moduloModal').style.display = 'none';
}

function editModulo(mod) {
    document.getElementById('mModalTitle').innerText = 'Editar Módulo';
    document.getElementById('moduloForm').action = `/central/billing/modulos/${mod.modulo_id}`;
    document.getElementById('mMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('idField').style.display = 'none';

    document.getElementById('mNombre').value = mod.nombre;
    document.getElementById('mDesc').value = mod.descripcion || '';
    document.getElementById('mPrecio').value = mod.precio_mensual;
    document.getElementById('mEsBase').checked = mod.es_base;
    document.getElementById('mActivo').checked = mod.activo;
    
    document.getElementById('moduloModal').style.display = 'flex';
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
.input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--b2); background: var(--s2); color: var(--t1); font-size: 14px; outline:none; }
.input:focus { border-color: var(--ac); }
</style>
@endsection
