@extends('layouts.central')

@section('title', 'Tenants')
@section('page-title', 'Gestión de Clientes (Tenants)')

@section('content')
<div class="page-hdr">
    <div class="page-title">Directorio de Empresas</div>
    <div class="fr">
        <button class="btn btn-primary">
            <span>⊞</span> Nuevo Tenant
        </button>
    </div>
</div>

<div class="card">
    <div class="card-hdr" style="margin-bottom:0">
        <form action="{{ route('central.tenants.index') }}" method="GET" style="display:flex; gap:12px; flex:1">
            <input type="text" name="search" value="{{ request('search') }}" 
                style="background:var(--s2); border:1px solid var(--b2); border-radius:8px; padding:8px 12px; font-size:12px; color:var(--tx); flex:1"
                placeholder="Buscar por nombre, RUT o ID...">
            
            <select name="estado" style="background:var(--s2); border:1px solid var(--b2); border-radius:8px; padding:8px 12px; font-size:12px; color:var(--tx)">
                <option value="">Todos los estados</option>
                <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                <option value="suspendido" {{ request('estado') == 'suspendido' ? 'selected' : '' }}>Suspendido</option>
            </select>

            <button type="submit" class="btn" style="background:var(--s2); border:1px solid var(--b2); color:var(--tx)">
                Filtrar
            </button>
        </form>
    </div>
</div>

@if(session('success'))
    <div style="padding:12px; background:rgba(0,229,160,.1); border:1px solid rgba(0,229,160,.2); color:var(--ok); border-radius:8px; margin-bottom:20px; font-size:12px;">
        {{ session('success') }}
    </div>
@endif

<div class="card" style="padding:0">
    <table class="tbl">
        <thead>
            <tr>
                <th>Empresa / ID</th>
                <th>Estado</th>
                <th>RUT</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenants as $tenant)
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:12px">
                        <div style="width:32px; height:32px; border-radius:6px; background:var(--s2); display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--ac); border:1px solid var(--b2)">
                            {{ substr($tenant->nombre, 0, 1) }}
                        </div>
                        <div>
                            <div style="font-weight:600">{{ $tenant->nombre }}</div>
                            <div style="font-family:var(--mono); font-size:10px; color:var(--t2)">{{ $tenant->id }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge" style="color: {{ $tenant->estado == 'activo' ? 'var(--ok)' : 'var(--err)' }}; background: {{ $tenant->estado == 'activo' ? 'rgba(0,229,160,.1)' : 'rgba(255,63,91,.1)' }}">
                        {{ strtoupper($tenant->estado) }}
                    </span>
                </td>
                <td>
                    <span style="font-family:var(--mono); color:var(--t2)">{{ $tenant->rut_empresa ?? 'N/A' }}</span>
                </td>
                <td>
                    <div style="display:flex; align-items:center; gap:8px">
                        @if($tenant->estado == 'activo')
                        <form action="{{ route('central.tenants.suspender', $tenant) }}" method="POST">
                            @csrf @method('PUT')
                            <button type="submit" class="btn" style="padding:4px 8px; font-size:16px; color:var(--t2)" title="Suspender">⊘</button>
                        </form>
                        @else
                        <form action="{{ route('central.tenants.reactivar', $tenant) }}" method="POST">
                            @csrf @method('PUT')
                            <button type="submit" class="btn" style="padding:4px 8px; font-size:16px; color:var(--t2)" title="Reactivar">✔</button>
                        </form>
                        @endif

                        <button onclick="confirmImpersonate('{{ $tenant->id }}')" class="btn" style="padding:4px 8px; font-size:16px; color:var(--t2)" title="Impersonar">⎆</button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center; padding:40px; color:var(--t3); font-style:italic">
                    No se encontraron tenants.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($tenants->hasPages())
    <div style="padding:16px; border-top:1px solid var(--b1);">
        {{ $tenants->links() }}
    </div>
    @endif
</div>

<script>
    async function confirmImpersonate(tenantId) {
        // 1. Cargar usuarios del tenant
        let usuarios = [];
        try {
            const res = await fetch(`/central/tenants/${tenantId}/usuarios`, {
                headers: { 'Accept': 'application/json' }
            });
            usuarios = await res.json();
        } catch (e) {
            alert('Error al cargar usuarios del tenant.');
            return;
        }

        if (!usuarios.length) {
            alert('Este tenant no tiene usuarios registrados.');
            return;
        }

        // 2. Mostrar modal de selección
        showImpersonateModal(tenantId, usuarios);
    }

    function showImpersonateModal(tenantId, usuarios) {
        // Remover modal previo si existe
        document.getElementById('impersonate-modal')?.remove();

        const roleColors = {
            admin:    '#ff6b35',
            cajero:   '#00e5a0',
            operario: '#4488ff',
            bodega:   '#7878a0',
            cliente:  '#f5c518',
        };

        const filas = usuarios.map(u => `
            <tr style="cursor:pointer;transition:background .1s" 
                onmouseover="this.style.background='var(--s3)'" 
                onmouseout="this.style.background=''"
                onclick="doImpersonate('${tenantId}', ${u.id})">
                <td style="padding:10px 12px">
                    <div style="font-weight:600;font-size:12px;color:var(--tx)">${u.nombre}</div>
                    <div style="font-family:var(--mono);font-size:10px;color:var(--t2)">${u.email}</div>
                </td>
                <td style="padding:10px 12px">
                    <span style="
                        display:inline-block;padding:2px 8px;border-radius:4px;
                        font-size:10px;font-weight:700;font-family:var(--mono);
                        background:${roleColors[u.rol] ?? '#333'}22;
                        color:${roleColors[u.rol] ?? '#aaa'}
                    ">${u.rol.toUpperCase()}</span>
                </td>
                <td style="padding:10px 12px;text-align:right">
                    <button onclick="event.stopPropagation();doImpersonate('${tenantId}', ${u.id})"
                        class="btn"
                        style="padding:4px 12px;background:rgba(224,64,251,.1);color:var(--ac);font-size:11px">
                        Entrar
                    </button>
                </td>
            </tr>
        `).join('');

        const modal = document.createElement('div');
        modal.id = 'impersonate-modal';
        modal.innerHTML = `
            <div onclick="this.parentElement.remove()" style="
                position:fixed;inset:0;background:rgba(0,0,0,.7);
                display:flex;align-items:center;justify-content:center;
                z-index:1000;padding:20px;backdrop-filter:blur(4px)">
                <div onclick="event.stopPropagation()" style="
                    background:var(--s1);border:1px solid var(--b2);border-radius:12px;
                    width:100%;max-width:480px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,.5)">
                    <div style="padding:16px 20px;border-bottom:1px solid var(--b1);
                                display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="font-weight:700;font-size:14px;color:var(--tx)">Seleccionar usuario</div>
                            <div style="font-size:11px;color:var(--t2);margin-top:2px">
                                Entrar al tenant como:
                            </div>
                        </div>
                        <button onclick="document.getElementById('impersonate-modal').remove()"
                            style="background:none;border:none;color:var(--t2);font-size:18px;cursor:pointer">✕</button>
                    </div>
                    <div style="max-height:400px;overflow-y:auto">
                        <table style="width:100%;border-collapse:collapse">
                            <thead>
                                <tr style="border-bottom:1px solid var(--b1)">
                                    <th style="text-align:left;padding:8px 12px;font-size:10px;
                                               color:var(--t2);letter-spacing:1px;text-transform:uppercase">
                                        Usuario
                                    </th>
                                    <th style="text-align:left;padding:8px 12px;font-size:10px;
                                               color:var(--t2);letter-spacing:1px;text-transform:uppercase">
                                        Rol
                                    </th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>${filas}</tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    async function doImpersonate(tenantId, userId) {
        document.getElementById('impersonate-modal')?.remove();

        try {
            const response = await fetch(`/central/tenants/${tenantId}/impersonar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userId }),
            });

            const data = await response.json();

            if (data.token) {
                window.open(data.url + '?token=' + data.token, '_blank');
            } else {
                alert('Error: ' + (data.message ?? 'Sin respuesta del servidor'));
            }
        } catch (e) {
            alert('Error crítico al intentar impersonar.');
        }
    }
</script>
@endsection
