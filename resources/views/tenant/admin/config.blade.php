@extends('tenant.layout')

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Configuración del Negocio</div>
            <div class="page-sub">Ajustes de rubro, módulos y personalización</div>
        </div>
        <div class="flex gap-2">
            <span id="saveStatus" style="font-size:12px; color:var(--ac); display:none;">✓ Guardado</span>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 380px; gap:24px; align-items: start;">
        <div class="flex flex-col gap-6">
            {{-- Presets de Industria --}}
            <div class="card">
                <h3 style="margin-bottom:12px; font-size:15px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Rubro e Industria
                </h3>
                <p style="color:var(--t2); font-size:13px; margin-bottom:16px;">Selecciona un preset para configurar automáticamente los módulos y terminología recomendada.</p>
                
                <div class="field">
                    <label class="label">Preset de Industria</label>
                    <div style="display:flex; gap:10px;">
                        <select id="presetSelector" class="flex-1">
                            <option value="retail" {{ $rubroConfig->industria_preset == 'retail' ? 'selected' : '' }}>Retail / Abarrotes</option>
                            <option value="mayorista" {{ $rubroConfig->industria_preset == 'mayorista' ? 'selected' : '' }}>Mayorista / Ferretería</option>
                            <option value="restaurante" {{ $rubroConfig->industria_preset == 'restaurante' ? 'selected' : '' }}>Restaurante</option>
                            <option value="delivery" {{ $rubroConfig->industria_preset == 'delivery' ? 'selected' : '' }}>Delivery / Dark Kitchen</option>
                            <option value="motel" {{ $rubroConfig->industria_preset == 'motel' ? 'selected' : '' }}>Motel / Hospedaje Horas</option>
                            <option value="hotel" {{ $rubroConfig->industria_preset == 'hotel' ? 'selected' : '' }}>Hotel / Alojamiento Días</option>
                            <option value="canchas" {{ $rubroConfig->industria_preset == 'canchas' ? 'selected' : '' }}>Canchas / Deportes</option>
                            <option value="medico" {{ $rubroConfig->industria_preset == 'medico' ? 'selected' : '' }}>Médico / Clínica</option>
                            <option value="dentista" {{ $rubroConfig->industria_preset == 'dentista' ? 'selected' : '' }}>Dentista</option>
                            <option value="legal" {{ $rubroConfig->industria_preset == 'legal' ? 'selected' : '' }}>Abogados / Estudio Jurídico</option>
                            <option value="tecnico" {{ $rubroConfig->industria_preset == 'tecnico' ? 'selected' : '' }}>Gasfíter / Técnico</option>
                            <option value="taller" {{ $rubroConfig->industria_preset == 'taller' ? 'selected' : '' }}>Taller Mecánico</option>
                            <option value="spa" {{ $rubroConfig->industria_preset == 'spa' ? 'selected' : '' }}>Salón de Belleza / Spa</option>
                            <option value="veterinaria" {{ $rubroConfig->industria_preset == 'veterinaria' ? 'selected' : '' }}>Veterinaria</option>
                            <option value="farmacia" {{ $rubroConfig->industria_preset == 'farmacia' ? 'selected' : '' }}>Farmacia</option>
                            <option value="gym" {{ $rubroConfig->industria_preset == 'gym' ? 'selected' : '' }}>Gimnasio / Fitness</option>
                            <option value="inmobiliaria" {{ $rubroConfig->industria_preset == 'inmobiliaria' ? 'selected' : '' }}>Inmobiliaria</option>
                            <option value="constructora" {{ $rubroConfig->industria_preset == 'constructora' ? 'selected' : '' }}>Constructora / Proyectos</option>
                            <option value="saas" {{ $rubroConfig->industria_preset == 'saas' ? 'selected' : '' }}>SaaS / Servicios Digitales</option>
                        </select>
                        <button class="btn btn-secondary" onclick="aplicarPreset()">Aplicar Preset</button>
                    </div>
                </div>
            </div>

            {{-- Configuración del Portal Público --}}
            <div class="card">
                <h3 style="margin-bottom:12px; font-size:15px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    Portal Público
                </h3>
                <p style="color:var(--t2); font-size:13px; margin-bottom:16px;">Configura la apariencia y datos de contacto de tu página web pública.</p>
                
                <form id="portalForm" onsubmit="event.preventDefault(); guardarPortal()">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="field">
                            <label class="label">¿Portal Activo?</label>
                            <select name="portal_activo">
                                <option value="1" {{ $config->portal_activo ? 'selected' : '' }}>Sí, visible</option>
                                <option value="0" {{ !$config->portal_activo ? 'selected' : '' }}>No, desactivado</option>
                            </select>
                        </div>
                        <div class="field">
                            <label class="label">Color Primario</label>
                            <input type="color" name="portal_color_primario" value="{{ $config->portal_color_primario ?? '#00e5a0' }}" style="height:38px; padding:2px;">
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">Descripción del Negocio</label>
                        <textarea name="portal_descripcion" rows="3" placeholder="Somos la mejor opción en..." style="width:100%; border:1px solid var(--b2); border-radius:8px; padding:10px; background:var(--s2); color:var(--t1);">{{ $config->portal_descripcion }}</textarea>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="field">
                            <label class="label">WhatsApp (Número)</label>
                            <input type="text" name="portal_whatsapp_numero" value="{{ $config->portal_whatsapp_numero }}" placeholder="+56912345678">
                        </div>
                        <div class="field">
                            <label class="label">Telegram (URL Bot)</label>
                            <input type="text" name="portal_telegram_url" value="{{ $config->portal_telegram_url }}" placeholder="https://t.me/TuBot">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div class="field">
                            <label class="label">Teléfono de Contacto</label>
                            <input type="text" name="portal_telefono" value="{{ $config->portal_telefono }}" placeholder="+56 2 2345 6789">
                        </div>
                        <div class="field">
                            <label class="label">Horario de Atención</label>
                            <input type="text" name="portal_horario" value="{{ $config->portal_horario }}" placeholder="Lun-Vie 09:00 - 18:00">
                        </div>
                    </div>

                    <div class="field">
                        <label class="label">Dirección Física</label>
                        <input type="text" name="portal_direccion" value="{{ $config->portal_direccion }}" placeholder="Av. Principal 123, Ciudad">
                    </div>

                    <div class="field">
                        <label class="label">URL del Logo</label>
                        <input type="text" name="portal_logo_url" value="{{ $config->portal_logo_url }}" placeholder="https://tusitio.com/logo.png">
                    </div>

                    <div style="margin-top:16px; display:flex; justify-content:space-between; align-items:center;">
                        <a href="{{ url('/portal') }}" target="_blank" style="font-size:12px; color:var(--ac); text-decoration:underline;">Ver mi portal público ↗</a>
                        <button type="submit" class="btn btn-primary">Guardar Cambios del Portal</button>
                    </div>
                </form>


            {{-- Gestor de Módulos Atómicos (Billing H19) --}}
            <div class="card" id="planBillingContainer">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="font-size:15px; font-weight:600; display:flex; align-items:center; gap:8px;">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="18"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Mi Plan y Módulos
                    </h3>
                    <div style="text-align:right;">
                        <div style="font-size:11px; color:var(--t2);">Costo Mensual Estimado</div>
                        <div id="costoTotalPlan" style="font-size:18px; font-weight:700; color:var(--ac);">Cargando...</div>
                    </div>
                </div>
                
                <div id="loadingPlan" style="padding:20px; text-align:center; color:var(--t2);">Cargando información del plan...</div>
                
                <div id="planContentWrapper" style="display:none;">
                    <h4 style="font-size:13px; font-weight:600; margin-bottom:8px; color:var(--t2);">Módulos Activos</h4>
                    <div id="modulosActivosList" style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 24px;"></div>
                    
                    <h4 style="font-size:13px; font-weight:600; margin-bottom:8px; color:var(--t2);">Módulos Disponibles</h4>
                    <div id="modulosDisponiblesList" style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;"></div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            {{-- Personalización de Etiquetas --}}
            <div class="card">
                <h3 style="margin-bottom:16px; font-size:15px; font-weight:600;">Etiquetas de UI</h3>
                <form id="labelsForm" onsubmit="event.preventDefault(); guardarLabels()">
                    <div class="field">
                        <label class="label">Nombre para "Producto"</label>
                        <input type="text" name="label_producto" value="{{ $rubroConfig->label_producto }}" placeholder="Ej: Artículo, Repuesto">
                    </div>
                    <div class="field">
                        <label class="label">Nombre para "Cliente"</label>
                        <input type="text" name="label_cliente" value="{{ $rubroConfig->label_cliente }}" placeholder="Ej: Paciente, Huésped">
                    </div>
                    <div class="field">
                        <label class="label">Nombre para "Recurso"</label>
                        <input type="text" name="label_recurso" value="{{ $rubroConfig->label_recurso }}" placeholder="Ej: Mesa, Cancha, Habitación">
                    </div>
                    <div class="field">
                        <label class="label">Nombre para "Operario"</label>
                        <input type="text" name="label_operario" value="{{ $rubroConfig->label_operario }}" placeholder="Ej: Vendedor, Garzón, Médico">
                    </div>
                    <button type="submit" class="btn btn-primary w-full" style="margin-top:10px;">Guardar Etiquetas</button>
                </form>
            </div>


            <div class="card bg-blue-900/10 border-blue-500/20">
                <h3 style="margin-bottom:12px; font-size:14px; font-weight:600; color:#60a5fa;">Bot de WhatsApp</h3>
                <p style="font-size:12px; color:var(--t2); margin-bottom:12px;">
                    Tu asistente está configurado como <b>"{{ $rubroConfig->industria_nombre }}"</b>. 
                    Puede reconfigurar el rubro automáticamente si se lo pides por chat.
                </p>
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="width:10px; height:10px; border-radius:50%; background:#22c55e;"></div>
                    <span style="font-size:12px; font-weight:600;">Servicio Activo</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    async function aplicarPreset() {
        const preset = document.getElementById('presetSelector').value;
        if (!confirm(`¿Estás seguro de que deseas aplicar el preset '${preset}'? Esto sobrescribirá los módulos activos y etiquetas.`)) return;
        
        try {
            const res = await api('POST', `/api/config/aplicar-preset/${preset}`);
            toast(res.message);
            setTimeout(() => location.reload(), 1000);
        } catch (e) {
            toast(e.message || 'Error al aplicar preset', 'err');
        }
    }

    document.addEventListener('DOMContentLoaded', loadMiPlan);

    let planData = null;

    async function loadMiPlan() {
        try {
            document.getElementById('loadingPlan').style.display = 'block';
            document.getElementById('planContentWrapper').style.display = 'none';

            const [resPlan, resDisp] = await Promise.all([
                api('GET', '/api/config/mi-plan'),
                api('GET', '/api/config/modulos-disponibles')
            ]);

            planData = resPlan;
            const formatClp = (num) => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP' }).format(num);

            document.getElementById('costoTotalPlan').innerText = formatClp(resPlan.total_mensual) + ' / mes';

            // Módulos Activos
            const divActivos = document.getElementById('modulosActivosList');
            divActivos.innerHTML = '';
            
            // Base fee explicit module representation
            divActivos.innerHTML += `
                <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; background:var(--s2); border-radius:8px; border:1px solid var(--ac);">
                    <div>
                        <div style="font-size:13px; font-weight:600; color:var(--ac);">Plan Base (Tarifa Fija)</div>
                        <div style="font-size:11px; color:var(--t2);">Infraestructura, POS y Soporte Mínimo</div>
                    </div>
                    <div style="font-size:13px; font-weight:600;">${formatClp(resPlan.tarifa_base)}</div>
                </div>
            `;

            resPlan.modulos_activos.forEach(mod => {
                if(mod.es_base) return; // Skip base modules (included in base fee)
                divActivos.innerHTML += `
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; background:var(--s2); border-radius:8px; border:1px solid var(--b2);">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <input type="checkbox" checked onchange="confirmarTogglingModulo('${mod.modulo_id}', 'desactivar')">
                            <div>
                                <div style="font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:130px;">${mod.nombre}</div>
                                <div style="font-size:11px; color:var(--t2);">ID: ${mod.modulo_id}</div>
                            </div>
                        </div>
                        <div style="font-size:13px; font-weight:500;">+ ${formatClp(mod.precio_mensual)}</div>
                    </div>
                `;
            });

            // Módulos Disponibles
            const divDisponibles = document.getElementById('modulosDisponiblesList');
            divDisponibles.innerHTML = '';
            
            if (resDisp.disponibles.length === 0) {
                divDisponibles.innerHTML = '<div style="font-size:12px; color:var(--t2);">No hay módulos adicionales disponibles.</div>';
            } else {
                resDisp.disponibles.forEach(mod => {
                    divDisponibles.innerHTML += `
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; background:var(--s2); border-radius:8px; border:1px dashed var(--b2);">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="checkbox" onchange="confirmarTogglingModulo('${mod.modulo_id}', 'activar')">
                                <div>
                                    <div style="font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:130px;">${mod.nombre}</div>
                                    <div style="font-size:11px; color:var(--t2);">ID: ${mod.modulo_id}</div>
                                </div>
                            </div>
                            <div style="font-size:13px; font-weight:500; color:var(--ac);">+ ${formatClp(mod.precio_mensual)}</div>
                        </div>
                    `;
                });
            }

            document.getElementById('loadingPlan').style.display = 'none';
            document.getElementById('planContentWrapper').style.display = 'block';

        } catch (e) {
            console.error(e);
            document.getElementById('loadingPlan').innerText = 'Error al cargar el plan de facturación.';
        }
    }

    async function confirmarTogglingModulo(id, accion) {
        // Fetch preview
        try {
            const preview = await api('GET', `/api/config/modulos/${id}/preview`);
            const df = preview.diferencia;
            const diffText = df > 0 ? `$${df}` : `-$${Math.abs(df)}`;

            const msj = accion === 'activar' 
                ? `¿Activar el módulo "${preview.modulo.nombre}"?\nEsto sumará ${diffText} / mes a tu facturación actual.\n\nTotal actual: $${preview.total_actual}\nNuevo total: $${preview.total_nuevo}`
                : `¿Desactivar el módulo "${preview.modulo.nombre}"?\nEsto restará ${diffText} / mes de tu facturación.\nPerderás acceso a las funciones correspondientes de inmediato.`;

            if(confirm(msj)) {
                await api('POST', `/api/config/modulos/${id}/${accion}`);
                toast(`Módulo ${accion}do correctamente`);
                loadMiPlan(); // Reload
            } else {
                loadMiPlan(); // Reset checkboxes visually
            }
        } catch (e) {
            toast(e.message || 'Error al modificar el módulo', 'err');
            loadMiPlan();
        }
    }

    async function guardarLabels() {
        const form = document.getElementById('labelsForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const res = await api('PUT', '/api/config/rubro', data);
            toast(res.message);
            setTimeout(() => location.reload(), 800);
        } catch (e) {
            toast(e.message || 'Error al guardar etiquetas', 'err');
        }
    }

    async function guardarPortal() {
        const form = document.getElementById('portalForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        try {
            const res = await api('PUT', '/api/config/portal', data);
            toast(res.message);
            // No recargamos para no perder el scroll, pero actualizamos el enlace si cambió algo
        } catch (e) {
            toast(e.message || 'Error al guardar configuración del portal', 'err');
        }
    }

</script>
@endpush
@endsection
