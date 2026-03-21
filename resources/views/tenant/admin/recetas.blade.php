@extends('tenant.layout')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Recetas e Ingredientes</div>
            <div class="page-sub">Fichas técnicas, costeo automático y producción</div>
        </div>
        <button class="btn btn-primary" onclick="toast('Formulario de nueva receta en desarrollo')">+ Nueva Receta</button>
    </div>

    {{-- KPIs --}}
    <div class="kpi-grid" style="margin-bottom:20px;">
        <div class="kpi-card">
            <div class="kpi-label">Recetas Activas</div>
            <div class="kpi-value" style="color:var(--accent);" id="kpi-recetas">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Producciones Hoy</div>
            <div class="kpi-value" style="color:var(--ok);" id="kpi-producciones">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Porciones Hoy</div>
            <div class="kpi-value" style="color:var(--warn);" id="kpi-porciones">0</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Margen Promedio</div>
            <div class="kpi-value" style="color:var(--ok);" id="kpi-margen">0%</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div style="display:flex; gap:4px; margin-bottom:16px; border-bottom:1px solid var(--b2);">
        <button class="btn btn-secondary tab-btn active" data-tab="recetas" onclick="switchTab('recetas', this)">Recetas</button>
        <button class="btn btn-secondary tab-btn" data-tab="costos" onclick="switchTab('costos', this)">Costo vs Precio</button>
        <button class="btn btn-secondary tab-btn" data-tab="producciones" onclick="switchTab('producciones', this)">Producciones</button>
    </div>

    {{-- Tab: Recetas --}}
    <div class="tab-content" id="tab-recetas">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Receta</th><th>Categoría</th><th>Porciones</th><th class="num">Costo/Porción</th><th class="num">Precio Venta</th><th class="num">Margen</th><th>Acciones</th></tr>
                    </thead>
                    <tbody id="recetasBody">
                        <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Costo vs Precio --}}
    <div class="tab-content" id="tab-costos" style="display:none;">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Receta</th><th>Categoría</th><th class="num">Costo</th><th class="num">Precio</th><th class="num">Margen %</th><th>Estado</th></tr>
                    </thead>
                    <tbody id="costosBody">
                        <tr><td colspan="6" style="text-align:center; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Producciones --}}
    <div class="tab-content" id="tab-producciones" style="display:none;">
        <div class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Receta</th><th>Batches</th><th>Porciones</th><th class="num">Costo</th><th>Estado</th><th>Usuario</th><th>Fecha</th></tr>
                    </thead>
                    <tbody id="produccionesBody">
                        <tr><td colspan="7" style="text-align:center; color:var(--t2);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarDashboard();
    cargarRecetas();
    cargarProducciones();
});

function switchTab(tab, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}

async function cargarDashboard() {
    try {
        const res = await api('GET', '/api/recetas/dashboard');
        document.getElementById('kpi-recetas').textContent = res.kpis.total_recetas || 0;
        document.getElementById('kpi-producciones').textContent = res.kpis.producciones_hoy || 0;
        document.getElementById('kpi-porciones').textContent = res.kpis.porciones_hoy || 0;
        document.getElementById('kpi-margen').textContent = (res.kpis.costo_promedio_margen || 0).toFixed(1) + '%';

        // Cargar reporte costos
        const costosBody = document.getElementById('costosBody');
        if (res.reporte?.length) {
            costosBody.innerHTML = res.reporte.map(r => `
                <tr>
                    <td style="font-weight:600;">${r.nombre}</td>
                    <td>${r.categoria || '-'}</td>
                    <td class="num">${fmt(r.costo_por_porcion)}</td>
                    <td class="num">${fmt(r.precio_venta)}</td>
                    <td class="num" style="color:${r.margen_pct >= 30 ? 'var(--ok)' : 'var(--err)'};">${r.margen_pct}%</td>
                    <td><span class="badge ${r.rentable ? 'badge-green' : 'badge-red'}">${r.rentable ? '✅ Rentable' : '⚠ Bajo margen'}</span></td>
                </tr>
            `).join('');
        } else {
            costosBody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--t2);">Sin datos</td></tr>';
        }
    } catch(e) { console.error('Error dashboard recetas', e); }
}

async function cargarRecetas() {
    try {
        const res = await api('GET', '/api/recetas?per_page=50');
        const recetas = res.data || [];
        const tbody = document.getElementById('recetasBody');
        if (!recetas.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--t2);">Sin recetas</td></tr>';
            return;
        }
        tbody.innerHTML = recetas.map(r => `
            <tr>
                <td>
                    <div style="font-weight:600;">${r.nombre}</div>
                    <div style="font-size:11px; color:var(--t2);">${r.ingredientes?.length || 0} ingredientes · ${r.tiempo_preparacion_min || 0} min</div>
                </td>
                <td>${r.categoria || '-'}</td>
                <td class="num">${r.porciones_por_batch}</td>
                <td class="num" style="color:var(--warn);">${fmt(r.costo_por_porcion)}</td>
                <td class="num" style="color:var(--accent);">${fmt(r.precio_venta)}</td>
                <td class="num" style="color:${r.margen_pct >= 30 ? 'var(--ok)' : 'var(--err)'};">${r.margen_pct}%</td>
                <td style="display:flex; gap:4px;">
                    <button class="btn btn-secondary btn-sm" onclick="recalcular(${r.id})">♻ Costear</button>
                    <button class="btn btn-secondary btn-sm" onclick="producir(${r.id})">🔥 Producir</button>
                </td>
            </tr>
        `).join('');
    } catch(e) { console.error('Error cargando recetas', e); }
}

async function cargarProducciones() {
    try {
        const res = await api('GET', '/api/producciones?per_page=25');
        const prods = res.data || [];
        const tbody = document.getElementById('produccionesBody');
        if (!prods.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--t2);">Sin producciones</td></tr>';
            return;
        }
        tbody.innerHTML = prods.map(p => {
            const estadoColors = { pendiente: 'badge-gray', en_proceso: 'badge-orange', completada: 'badge-green', cancelada: 'badge-red' };
            return `
            <tr>
                <td style="font-weight:600;">${p.receta?.nombre || '-'}</td>
                <td class="num">${p.cantidad_batches}</td>
                <td class="num">${p.porciones_producidas}</td>
                <td class="num" style="color:var(--warn);">${fmt(p.costo_total)}</td>
                <td><span class="badge ${estadoColors[p.estado]}">${p.estado}</span></td>
                <td>${p.usuario?.nombre || '-'}</td>
                <td>${p.created_at?.substring(0,10)}</td>
            </tr>`;
        }).join('');
    } catch(e) { console.error('Error cargando producciones', e); }
}

async function recalcular(recetaId) {
    try {
        const res = await api('POST', `/api/recetas/${recetaId}/recalcular`);
        toast(res.message);
        cargarRecetas();
        cargarDashboard();
    } catch(e) { toast('Error recalculando costos', 'err'); }
}

async function producir(recetaId) {
    const batches = prompt('¿Cuántos batches producir?', '1');
    if (!batches || isNaN(batches)) return;

    // Primero verificar stock
    try {
        const check = await api('POST', `/api/recetas/${recetaId}/verificar-stock`, { batches: parseInt(batches) });
        if (!check.puede_producir) {
            const faltantes = check.faltantes.map(f => `${f.nombre}: faltan ${f.faltante} ${f.unidad}`).join('\n');
            toast('Stock insuficiente:\n' + faltantes, 'err');
            return;
        }
    } catch(e) { toast('Error verificando stock', 'err'); return; }

    try {
        const res = await api('POST', `/api/recetas/${recetaId}/producir`, { batches: parseInt(batches) });
        toast(res.message);
        cargarRecetas();
        cargarProducciones();
        cargarDashboard();
    } catch(e) { toast('Error al producir', 'err'); }
}
</script>
@endpush
@endsection
