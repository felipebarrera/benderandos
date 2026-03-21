@extends('tenant.layout')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Gestión de WhatsApp Bot</div>
            <div class="page-sub">Estado de integración, logs y configuración del asistente</div>
        </div>
        <div style="display:flex; gap:10px;">
            <button class="btn btn-secondary" onclick="testConnection()">Probar Conexión</button>
            <button class="btn btn-primary" onclick="guardarBotConfig()">Guardar Cambios</button>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 350px; gap:24px; align-items: start;">
        <div class="flex flex-col gap-6">

            {{-- Estado del Servicio --}}
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="font-size:16px; font-weight:600;">Estado de Integración</h3>
                    <div id="serviceStatusBadge" class="badge badge-green">Verificando...</div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
                    <div style="padding:14px; background:var(--s2); border-radius:8px; border:1px solid var(--b2);">
                        <div style="font-size:11px; color:var(--t2); margin-bottom:4px;">Endpoint ERP</div>
                        <div style="font-family:var(--mono); font-size:12px; color:var(--accent);">{{ url('/api/internal') }}</div>
                    </div>
                    <div style="padding:14px; background:var(--s2); border-radius:8px; border:1px solid var(--b2);">
                        <div style="font-size:11px; color:var(--t2); margin-bottom:4px;">LLM Provider</div>
                        <div style="font-family:var(--mono); font-size:13px; color:var(--accent);" id="llm-provider">-</div>
                    </div>
                    <div style="padding:14px; background:var(--s2); border-radius:8px; border:1px solid var(--b2);">
                        <div style="font-size:11px; color:var(--t2); margin-bottom:4px;">Modelo</div>
                        <div style="font-family:var(--mono); font-size:12px;" id="llm-model">-</div>
                    </div>
                    <div style="padding:14px; background:var(--s2); border-radius:8px; border:1px solid var(--b2);">
                        <div style="font-size:11px; color:var(--t2); margin-bottom:4px;">Uptime</div>
                        <div style="font-family:var(--mono); font-size:13px; color:var(--ok);" id="bot-uptime">-</div>
                    </div>
                    <div style="padding:14px; background:var(--s2); border-radius:8px; border:1px solid var(--b2);">
                        <div style="font-size:11px; color:var(--t2); margin-bottom:4px;">Mensajes hoy</div>
                        <div style="font-family:var(--mono); font-size:18px; font-weight:700;" id="mensajes-hoy">-</div>
                    </div>
                    <div style="padding:14px; background:var(--s2); border-radius:8px; border:1px solid var(--b2);">
                        <div style="font-size:11px; color:var(--t2); margin-bottom:4px;">Conversaciones activas</div>
                        <div style="font-family:var(--mono); font-size:18px; font-weight:700; color:var(--accent);" id="conv-activas">-</div>
                    </div>
                </div>
            </div>

            {{-- Logs de Actividad --}}
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="font-size:16px; font-weight:600;">Logs Recientes</h3>
                    <span style="font-size:11px; color:var(--t2);">Actualiza cada 15s</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Evento</th>
                                <th>Dir.</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="logsTable">
                            <tr><td colspan="4" style="text-align:center; padding:20px; color:var(--t2);">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-6">

            {{-- Configuración del Bot --}}
            <div class="card">
                <h3 style="margin-bottom:16px; font-size:16px; font-weight:600;">Configuración</h3>
                <form id="botConfigForm">
                    <div class="field">
                        <label class="label">Nombre del Asistente</label>
                        <input type="text" name="nombre_bot" id="bot_nombre" value="BenderAndos">
                    </div>
                    <div class="field">
                        <label class="label">Personalidad / Tono</label>
                        <select name="personalidad" id="bot_personalidad">
                            <option value="formal">Formal y Profesional</option>
                            <option value="casual">Casual y Amigable</option>
                            <option value="agresiva">Ventas Directas</option>
                        </select>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; margin-top:16px;">
                        <input type="checkbox" name="activo" id="bot_activo" checked>
                        <label for="bot_activo" style="font-size:14px;">Bot Activo</label>
                    </div>
                </form>
            </div>

            {{-- Info LLM (solo lectura) --}}
            <div class="card">
                <h3 style="margin-bottom:12px; font-size:14px; font-weight:600; color:var(--t2);">Motor IA (desde .env del bot)</h3>
                <div style="display:flex; flex-direction:column; gap:8px; font-size:13px;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--t2);">Provider</span>
                        <span style="font-family:var(--mono);" id="llm-provider-detail">-</span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--t2);">Modelo</span>
                        <span style="font-family:var(--mono); font-size:12px;" id="llm-model-detail">-</span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--t2);">Temperature</span>
                        <span style="font-family:var(--mono);" id="llm-temp">-</span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--t2);">Max tokens</span>
                        <span style="font-family:var(--mono);" id="llm-tokens">-</span>
                    </div>
                </div>
            </div>

            {{-- Acciones Rápidas --}}
            <div class="card" style="border:1px solid rgba(245,197,24,.2); background:rgba(245,197,24,.04);">
                <h3 style="margin-bottom:12px; font-size:14px; font-weight:600; color:var(--warn);">Acciones Rápidas</h3>
                <div class="flex flex-col gap-2">
                    <button class="btn btn-secondary w-full" style="justify-content:flex-start;" onclick="testConnection()">
                        <span style="margin-right:8px;">⚡</span> Probar conexión bot
                    </button>
                    <button class="btn btn-secondary w-full" style="justify-content:flex-start;" onclick="cargarLogs()">
                        <span style="margin-right:8px;">↺</span> Refrescar logs ahora
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarBotStatus();
    cargarLogs();
    setInterval(cargarLogs, 15000);
});

async function cargarBotStatus() {
    try {
        const res = await api('GET', '/api/bot/config');
        const cfg    = res.config    || {};
        const status = res.bot_status;
        const llm    = res.llm_config || {};

        // Config local
        document.getElementById('bot_nombre').value       = cfg.nombre_bot   || 'BenderAndos';
        document.getElementById('bot_personalidad').value = cfg.personalidad  || 'formal';
        document.getElementById('bot_activo').checked     = cfg.activo        ?? true;

        const badge = document.getElementById('serviceStatusBadge');
        if (res.bot_online && status) {
            badge.className   = 'badge badge-green';
            badge.textContent = 'En Línea';

            document.getElementById('llm-provider').textContent        = llm.llm_provider || '-';
            document.getElementById('llm-model').textContent           = llm.llm_model    || '-';
            document.getElementById('bot-uptime').textContent          = formatUptime(status.uptime);
            document.getElementById('mensajes-hoy').textContent        = status.mensajes_hoy           ?? '-';
            document.getElementById('conv-activas').textContent        = status.conversaciones_activas ?? '-';
            document.getElementById('llm-provider-detail').textContent = llm.llm_provider    || '-';
            document.getElementById('llm-model-detail').textContent    = llm.llm_model       || '-';
            document.getElementById('llm-temp').textContent            = llm.llm_temperature ?? '-';
            document.getElementById('llm-tokens').textContent          = llm.llm_max_tokens  ?? '-';
        } else {
            badge.className   = 'badge badge-red';
            badge.textContent = 'Sin conexión';
        }
    } catch (e) {
        document.getElementById('serviceStatusBadge').className   = 'badge badge-red';
        document.getElementById('serviceStatusBadge').textContent = 'Error';
    }
}

async function cargarLogs() {
    try {
        const res  = await api('GET', '/api/bot/logs?limit=20');
        const logs = res.logs || [];

        if (!logs.length) {
            document.getElementById('logsTable').innerHTML =
                '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--t2);">Sin mensajes aún</td></tr>';
            return;
        }

        document.getElementById('logsTable').innerHTML = logs.map(l => {
            const time     = new Date(l.timestamp).toLocaleTimeString('es-CL', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
            const dirClass = l.direccion === 'IN' ? 'badge-blue' : 'badge-purple';
            const stClass  = l.estado === 'HUMANO' ? 'badge-warn' : 'badge-green';
            return `<tr>
                <td class="mono" style="font-size:12px;">${time}</td>
                <td style="font-size:12px; max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${l.evento}</td>
                <td><span class="badge ${dirClass}">${l.direccion}</span></td>
                <td><span class="badge ${stClass}">${l.estado}</span></td>
            </tr>`;
        }).join('');
    } catch (e) {
        document.getElementById('logsTable').innerHTML =
            '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--err);">Bot no disponible</td></tr>';
    }
}

async function guardarBotConfig() {
    try {
        const res = await api('PUT', '/api/bot/config', {
            nombre_bot:   document.getElementById('bot_nombre').value,
            personalidad: document.getElementById('bot_personalidad').value,
            activo:       document.getElementById('bot_activo').checked,
        });
        toast(res.message || 'Configuración guardada');
    } catch (e) {
        toast(e.message || 'Error guardando config', 'err');
    }
}

async function testConnection() {
    toast('Probando conexión...');
    try {
        const res = await api('GET', '/api/bot/test-connection');
        toast((res.success ? '✅ ' : '❌ ') + res.message, res.success ? 'ok' : 'err');
        if (res.success) cargarBotStatus();
    } catch (e) {
        toast('❌ Bot no disponible', 'err');
    }
}

function formatUptime(s) {
    if (!s) return '-';
    const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = Math.floor(s % 60);
    if (h > 0) return `${h}h ${m}m`;
    if (m > 0) return `${m}m ${sec}s`;
    return `${sec}s`;
}
</script>
@endpush
@endsection