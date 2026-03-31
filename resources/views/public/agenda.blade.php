@php
    $tenantNombre = tenant('nombre') ?? config('app.name', 'Clínica');
    $colorAc      = $config->color_primario ?? '#0ea5e9';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $config->titulo_landing ?? $tenantNombre }} · Agenda tu consulta</title>
<meta name="description" content="{{ $config->descripcion_landing ?? 'Reserva tu hora médica en línea. Sin llamadas, sin esperas.' }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════════════════════════
   DESIGN TOKENS
═══════════════════════════════════════════════════════════════ */
:root {
  --ac:       {{ $colorAc }};
  --ac-dark:  color-mix(in srgb, var(--ac) 75%, #000);
  --ac-light: color-mix(in srgb, var(--ac) 12%, #fff);
  --ac-mid:   color-mix(in srgb, var(--ac) 25%, #fff);

  --bg:    #f0f4f8;
  --bg2:   #e4ecf4;
  --white: #ffffff;
  --ink:   #0a1628;
  --ink2:  #3a4a5c;
  --ink3:  #7a8fa6;
  --ink4:  #bccad8;
  --line:  rgba(10,22,40,.08);

  --r-sm:  8px;
  --r:     14px;
  --r-lg:  22px;
  --r-xl:  32px;

  --shadow-sm:  0 1px 4px rgba(10,22,40,.08);
  --shadow:     0 4px 20px rgba(10,22,40,.10);
  --shadow-lg:  0 12px 48px rgba(10,22,40,.15);
  --shadow-ac:  0 8px 32px color-mix(in srgb, var(--ac) 35%, transparent);
}

/* ═══════════════════════════════════════════════════════════════
   RESET + BASE
═══════════════════════════════════════════════════════════════ */
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  background: var(--bg);
  color: var(--ink);
  font-family: 'DM Sans', sans-serif;
  font-size: 15px;
  line-height: 1.6;
  min-height: 100vh;
  overflow-x: hidden;
}
img{display:block;max-width:100%}
button{font-family:inherit;cursor:pointer}
input,textarea,select{font-family:inherit}

/* ═══════════════════════════════════════════════════════════════
   NAV
═══════════════════════════════════════════════════════════════ */
.nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 200;
  height: 64px;
  background: rgba(255,255,255,.88);
  backdrop-filter: blur(20px) saturate(1.4);
  border-bottom: 1px solid var(--line);
  display: flex; align-items: center;
  padding: 0 max(24px, env(safe-area-inset-left));
  gap: 16px;
}
.nav-logo-wrap {
  display: flex; align-items: center; gap: 10px;
  text-decoration: none;
}
.nav-logo {
  width: 36px; height: 36px; border-radius: 10px;
  background: var(--ac);
  display: flex; align-items: center; justify-content: center;
  font-family: 'Syne', sans-serif;
  font-weight: 800; font-size: 15px; color: #fff;
  letter-spacing: -0.5px;
  flex-shrink: 0;
  box-shadow: 0 2px 8px color-mix(in srgb, var(--ac) 40%, transparent);
}
.nav-name {
  font-family: 'Syne', sans-serif;
  font-weight: 700; font-size: 16px;
  color: var(--ink); letter-spacing: -0.3px;
}
.nav-tagline {
  font-size: 11px; color: var(--ink3);
  margin-top: -2px;
}
.nav-pills {
  display: flex; gap: 4px; margin-left: auto; align-items: center;
}
.nav-pill {
  padding: 6px 16px; border-radius: 40px;
  font-size: 13px; font-weight: 500;
  color: var(--ink2); text-decoration: none;
  transition: all .15s;
  border: none; background: transparent;
}
.nav-pill:hover { background: var(--bg); color: var(--ink); }
.nav-cta {
  background: var(--ac);
  color: #fff;
  padding: 8px 20px; border-radius: 40px;
  font-size: 13px; font-weight: 600;
  text-decoration: none; border: none;
  box-shadow: 0 2px 8px color-mix(in srgb, var(--ac) 35%, transparent);
  transition: all .15s;
}
.nav-cta:hover {
  background: var(--ac-dark);
  transform: translateY(-1px);
  box-shadow: 0 4px 16px color-mix(in srgb, var(--ac) 45%, transparent);
}
@media(max-width:640px) {
  .nav-tagline { display:none; }
  .nav-pill { display:none; }
}

/* ═══════════════════════════════════════════════════════════════
   HERO
═══════════════════════════════════════════════════════════════ */
.hero {
  padding-top: 64px; /* nav height */
  background: linear-gradient(145deg, #f0f6ff 0%, #e8f3ff 40%, #f0f4f8 100%);
  position: relative; overflow: hidden;
}
.hero::before {
  content: '';
  position: absolute; top: -120px; right: -80px;
  width: 600px; height: 600px; border-radius: 50%;
  background: radial-gradient(circle, color-mix(in srgb, var(--ac) 8%, transparent) 0%, transparent 70%);
  pointer-events: none;
}
.hero::after {
  content: '';
  position: absolute; bottom: -60px; left: -40px;
  width: 400px; height: 400px; border-radius: 50%;
  background: radial-gradient(circle, color-mix(in srgb, var(--ac) 5%, transparent) 0%, transparent 70%);
  pointer-events: none;
}
.hero-inner {
  max-width: 1100px; margin: 0 auto;
  padding: 64px 24px 56px;
  display: grid; grid-template-columns: 1fr 480px; gap: 48px; align-items: center;
  position: relative; z-index: 1;
}
@media(max-width:900px) {
  .hero-inner { grid-template-columns: 1fr; text-align: center; }
  .hero-badges { justify-content: center !important; }
}
.hero-badge {
  display: inline-flex; align-items: center; gap: 7px;
  background: var(--ac-light);
  border: 1px solid var(--ac-mid);
  border-radius: 40px; padding: 5px 14px;
  font-size: 11.5px; font-weight: 600;
  color: var(--ac-dark); letter-spacing: .3px; margin-bottom: 18px;
}
.hero-badge-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--ac); animation: pulse 2s infinite;
}
@keyframes pulse {
  0%,100%{opacity:1;transform:scale(1)}
  50%{opacity:.5;transform:scale(1.3)}
}
.hero-h1 {
  font-family: 'Syne', sans-serif;
  font-size: clamp(32px, 5vw, 54px);
  font-weight: 800; line-height: 1.1;
  letter-spacing: -2px; color: var(--ink); margin-bottom: 18px;
}
.hero-h1 em { font-style: normal; color: var(--ac); }
.hero-p {
  font-size: 16px; color: var(--ink2); line-height: 1.7;
  max-width: 460px; margin-bottom: 32px;
}
.hero-badges { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 32px; }
.hbadge {
  background: var(--white); border: 1px solid var(--line);
  border-radius: 40px; padding: 7px 16px;
  font-size: 12.5px; font-weight: 500; color: var(--ink2);
  display: flex; align-items: center; gap: 7px;
  box-shadow: var(--shadow-sm);
}
.hero-cta {
  display: inline-flex; align-items: center; gap: 10px;
  background: var(--ac); color: #fff;
  padding: 14px 28px; border-radius: var(--r);
  font-size: 15px; font-weight: 600;
  text-decoration: none; border: none;
  box-shadow: var(--shadow-ac); transition: all .2s;
}
.hero-cta:hover {
  background: var(--ac-dark); transform: translateY(-2px);
  box-shadow: 0 12px 40px color-mix(in srgb, var(--ac) 45%, transparent);
}
.hero-avail-card {
  background: var(--white); border-radius: var(--r-lg);
  padding: 24px; box-shadow: var(--shadow-lg); border: 1px solid var(--line);
}
.hac-title {
  font-size: 12px; font-weight: 700; letter-spacing: .8px;
  text-transform: uppercase; color: var(--ink3); margin-bottom: 16px;
}
.hac-list { display: flex; flex-direction: column; gap: 10px; }
.hac-row {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 12px; border-radius: var(--r-sm);
  background: var(--bg); cursor: pointer; transition: all .15s;
}
.hac-row:hover { background: var(--ac-light); }
.hac-dot {
  width: 36px; height: 36px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; flex-shrink: 0;
}
.hac-info { flex: 1; }
.hac-name { font-size: 13px; font-weight: 600; color: var(--ink); }
.hac-esp  { font-size: 11px; color: var(--ink3); }
.hac-slots { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 6px; }
.hac-slot {
  background: var(--ac-light); border: 1px solid var(--ac-mid);
  border-radius: 6px; padding: 2px 8px;
  font-family: 'DM Mono', monospace; font-size: 10.5px;
  font-weight: 500; color: var(--ac-dark);
}
.hac-badge-avail {
  font-family: 'DM Mono', monospace; font-size: 10px;
  font-weight: 500; color: #059669; background: #d1fae5;
  border: 1px solid #a7f3d0; border-radius: 6px;
  padding: 2px 8px; flex-shrink: 0;
}

/* ═══════════════════════════════════════════════════════════════
   MAIN CONTENT
═══════════════════════════════════════════════════════════════ */
.site-main { max-width: 1100px; margin: 0 auto; padding: 0 24px; }
.sec-label {
  font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
  text-transform: uppercase; color: var(--ac); margin-bottom: 8px;
}
.sec-title {
  font-family: 'Syne', sans-serif; font-size: clamp(24px, 3.5vw, 36px);
  font-weight: 800; letter-spacing: -1px; color: var(--ink);
  line-height: 1.2; margin-bottom: 12px;
}
.sec-sub { font-size: 15px; color: var(--ink2); max-width: 500px; line-height: 1.7; }
.especialidades-section { padding: 72px 0 48px; }
.esp-grid { margin-top: 36px; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
.esp-card {
  background: var(--white); border: 1.5px solid var(--line);
  border-radius: var(--r-lg); padding: 28px 22px; cursor: pointer;
  transition: all .2s cubic-bezier(.4,0,.2,1); position: relative; overflow: hidden;
}
.esp-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: var(--ac); transform: scaleX(0); transform-origin: left;
  transition: transform .25s; border-radius: 3px 3px 0 0;
}
.esp-card:hover { border-color: color-mix(in srgb, var(--ac) 30%, transparent); box-shadow: var(--shadow-lg); transform: translateY(-4px); }
.esp-card:hover::before { transform: scaleX(1); }
.esp-card.activa { border-color: var(--ac); background: var(--ac-light); box-shadow: 0 0 0 4px var(--ac-mid), var(--shadow); transform: translateY(-2px); }
.esp-card.activa::before { transform: scaleX(1); }
.esp-icon { width: 52px; height: 52px; border-radius: 14px; background: var(--bg); display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 16px; transition: all .2s; }
.esp-card:hover .esp-icon, .esp-card.activa .esp-icon { background: var(--ac-light); transform: scale(1.05); }
.esp-nombre { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700; color: var(--ink); margin-bottom: 5px; }
.esp-desc { font-size: 12.5px; color: var(--ink3); line-height: 1.5; margin-bottom: 14px; }
.esp-count { font-family: 'DM Mono', monospace; font-size: 11px; font-weight: 500; color: var(--ac-dark); background: var(--ac-light); border: 1px solid var(--ac-mid); border-radius: 20px; padding: 2px 10px; display: inline-block; }
.esp-arrow { position: absolute; bottom: 20px; right: 20px; width: 28px; height: 28px; border-radius: 50%; background: var(--ac); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; opacity: 0; transform: translate(4px, 4px); transition: all .2s; }
.esp-card:hover .esp-arrow, .esp-card.activa .esp-arrow { opacity: 1; transform: translate(0,0); }

/* ═══════════════════════════════════════════════════════════════
   AGENDA PANEL
═══════════════════════════════════════════════════════════════ */
.agenda-panel-wrap { display: none; background: var(--white); border: 1.5px solid var(--line); border-radius: var(--r-xl); box-shadow: var(--shadow-lg); overflow: hidden; margin-bottom: 72px; animation: slideDown .3s cubic-bezier(.4,0,.2,1); }
.agenda-panel-wrap.visible { display: block; }
@keyframes slideDown { from { opacity:0; transform: translateY(-16px); } to { opacity:1; transform: translateY(0); } }
.ap-header { padding: 24px 28px 20px; border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; background: linear-gradient(135deg, var(--ac-light), var(--white)); }
.ap-header-left { display: flex; align-items: center; gap: 14px; }
.ap-icon { width: 44px; height: 44px; border-radius: 12px; background: var(--ac-light); border: 1.5px solid var(--ac-mid); display: flex; align-items: center; justify-content: center; font-size: 22px; }
.ap-esp-nombre { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800; color: var(--ink); letter-spacing: -0.5px; }
.ap-esp-sub { font-size: 13px; color: var(--ink3); }
.ap-fecha-nav { display: flex; align-items: center; gap: 8px; }
.ap-fecha-btn { width: 34px; height: 34px; border-radius: 50%; border: 1.5px solid var(--line); background: var(--white); display: flex; align-items: center; justify-content: center; font-size: 16px; color: var(--ink2); cursor: pointer; transition: all .15s; }
.ap-fecha-btn:hover { border-color: var(--ac); color: var(--ac); }
.ap-fecha-display { font-family: 'DM Mono', monospace; font-size: 14px; font-weight: 500; color: var(--ink); min-width: 160px; text-align: center; }
.ap-hoy-btn { padding: 5px 14px; border-radius: 20px; border: 1.5px solid var(--ac-mid); background: var(--ac-light); color: var(--ac-dark); font-size: 12px; font-weight: 600; cursor: pointer; transition: all .15s; }
.ap-hoy-btn:hover { background: var(--ac); color: #fff; border-color: var(--ac); }
.ap-semana { display: grid; grid-template-columns: repeat(7,1fr); border-bottom: 1px solid var(--line); }
.ap-dia-col { border-right: 1px solid var(--line); padding: 14px 8px 12px; text-align: center; cursor: pointer; transition: background .15s; }
.ap-dia-col:last-child { border-right: none; }
.ap-dia-col:hover { background: var(--bg); }
.ap-dia-col.sel { background: var(--ac-light); }
.ap-dia-lbl { font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--ink3); margin-bottom: 4px; }
.ap-dia-num { font-family: 'DM Mono', monospace; font-size: 18px; font-weight: 500; color: var(--ink); margin-bottom: 4px; }
.ap-dia-col.hoy .ap-dia-num { color: var(--ac); font-weight: 700; }
.ap-dia-col.sel .ap-dia-num { color: var(--ac-dark); }
.ap-dia-dots { display: flex; justify-content: center; gap: 3px; flex-wrap: wrap; }
.ap-dia-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--ac); opacity: .5; }
.ap-body { display: grid; grid-template-columns: 200px 1fr; min-height: 300px; }
.ap-body-sidebar { border-right: 1px solid var(--line); padding: 20px 0; }
.ap-doc-item { display: flex; align-items: center; gap: 10px; padding: 10px 16px; cursor: pointer; transition: background .15s; border-left: 3px solid transparent; }
.ap-doc-item:hover { background: var(--bg); }
.ap-doc-item.sel { background: var(--ac-light); border-left-color: var(--ac); }
.ap-doc-avatar { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }
.ap-doc-nombre { font-size: 12.5px; font-weight: 600; color: var(--ink); }
.ap-doc-slots  { font-size: 10.5px; color: var(--ink3); }
.ap-body-slots { padding: 20px; display: flex; flex-direction: column; gap: 8px; }
.slots-fila { background: var(--bg); border: 1px solid var(--line); border-radius: var(--r); padding: 16px; display: none; }
.slots-fila.visible { display: block; }
.sf-header { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.sf-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }
.sf-nombre { font-size: 13px; font-weight: 700; color: var(--ink); }
.sf-esp    { font-size: 11px; color: var(--ink3); }
.sf-slots-row { display: flex; flex-wrap: wrap; gap: 6px; }
.sf-slot { background: var(--white); border: 1.5px solid var(--line); border-radius: var(--r-sm); padding: 7px 14px; font-family: 'DM Mono', monospace; font-size: 13px; font-weight: 500; color: var(--ink2); cursor: pointer; transition: all .15s; display: flex; align-items: center; gap: 6px; }
.sf-slot:hover { border-color: var(--ac); color: var(--ac-dark); background: var(--ac-light); transform: translateY(-1px); box-shadow: var(--shadow-sm); }
.sf-slot.sel { background: var(--ac); border-color: var(--ac); color: #fff; box-shadow: var(--shadow-ac); }
.sf-slot-dot { width: 6px; height: 6px; border-radius: 50%; background: #22c55e; flex-shrink: 0; }
.sf-slot.sel .sf-slot-dot { background: rgba(255,255,255,.7); }
.sf-empty { font-size: 13px; color: var(--ink3); padding: 8px 0; font-style: italic; }
.ap-loading { grid-column: 1/-1; display: flex; align-items: center; justify-content: center; padding: 60px; color: var(--ink3); font-size: 14px; gap: 10px; }
.spinner { width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--line); border-top-color: var(--ac); animation: spin .7s linear infinite; }
@keyframes spin { to{transform:rotate(360deg)} }
.ap-todos-btn { padding: 6px 16px; border-radius: 20px; border: 1.5px solid var(--line); background: var(--white); color: var(--ink2); font-size: 12px; font-weight: 600; cursor: pointer; transition: all .15s; margin: 12px 16px 0; }
.ap-todos-btn:hover { border-color: var(--ac); color: var(--ac); }
.ap-todos-btn.activo { background: var(--ac-light); border-color: var(--ac); color: var(--ac-dark); }
@media(max-width:640px) { .ap-body { grid-template-columns: 1fr; } .ap-body-sidebar { border-right: none; border-bottom: 1px solid var(--line); padding: 12px 0; } .ap-doc-item { padding: 8px 14px; } }

/* ═══════════════════════════════════════════════════════════════
   MODAL & EXITO & TRUST & FORMS (Minified for setup)
═══════════════════════════════════════════════════════════════ */
.modal-overlay { position: fixed; inset: 0; z-index: 500; background: rgba(10,22,40,.55); backdrop-filter: blur(6px); display: none; align-items: center; justify-content: center; padding: 20px; }
.modal-overlay.open { display: flex; }
.modal-box { background: var(--white); border-radius: var(--r-xl); width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-lg); animation: modalIn .25s cubic-bezier(.4,0,.2,1); }
@keyframes modalIn { from { opacity:0; transform:scale(.95) translateY(10px); } to { opacity:1; transform:scale(1)  translateY(0); } }
.modal-header { padding: 24px 24px 0; display: flex; align-items: flex-start; justify-content: space-between; position: sticky; top: 0; background: var(--white); border-bottom: 1px solid var(--line); padding-bottom: 16px; }
.modal-title { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800; color: var(--ink); letter-spacing: -0.5px; }
.modal-sub { font-size: 13px; color: var(--ink3); margin-top: 2px; }
.modal-close { width: 32px; height: 32px; border-radius: 50%; border: 1.5px solid var(--line); background: var(--bg); display: flex; align-items: center; justify-content: center; font-size: 16px; color: var(--ink3); cursor: pointer; transition: all .15s; flex-shrink: 0; }
.modal-close:hover { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }
.modal-body { padding: 20px 24px; }
.resumen-cita { background: var(--bg); border: 1.5px solid var(--line); border-radius: var(--r); padding: 16px; margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.rc-label { font-size: 10.5px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--ink3); margin-bottom: 3px; }
.rc-val { font-size: 14px; font-weight: 600; color: var(--ink); }
.rc-val.money { font-family: 'DM Mono', monospace; color: var(--ac-dark); font-size: 16px; }
.modal-steps { display: flex; gap: 0; margin-bottom: 20px; }
.ms-step { flex: 1; text-align: center; position: relative; }
.ms-step::after { content: ''; position: absolute; top: 13px; left: 50%; right: -50%; height: 2px; background: var(--line); z-index: 0; }
.ms-step:last-child::after { display: none; }
.ms-num { width: 26px; height: 26px; border-radius: 50%; border: 2px solid var(--line); background: var(--white); font-family: 'DM Mono', monospace; font-size: 11px; font-weight: 700; color: var(--ink3); display: flex; align-items: center; justify-content: center; margin: 0 auto 5px; position: relative; z-index: 1; transition: all .2s; }
.ms-lbl { font-size: 10px; font-weight: 600; color: var(--ink3); }
.ms-step.done .ms-num { background: var(--ac); border-color: var(--ac); color: #fff; }
.ms-step.done .ms-lbl { color: var(--ac-dark); }
.ms-step.active .ms-num { border-color: var(--ac); color: var(--ac); }
.ms-step.active .ms-lbl { color: var(--ink2); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-field { margin-bottom: 14px; }
.form-label { display: block; font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--ink3); margin-bottom: 6px; }
.form-input { width: 100%; padding: 11px 14px; background: var(--bg); border: 1.5px solid var(--line); border-radius: var(--r-sm); color: var(--ink); font-size: 14px; outline: none; transition: border-color .15s, background .15s; }
.form-input:focus { border-color: var(--ac); background: var(--white); }
.form-textarea { min-height: 70px; resize: vertical; }
.pago-metodos { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
.pago-opt { border: 1.5px solid var(--line); border-radius: var(--r); padding: 14px; cursor: pointer; transition: all .15s; text-align: center; background: var(--bg); }
.pago-opt:hover { border-color: var(--ac-mid); background: var(--ac-light); }
.pago-opt.sel { border-color: var(--ac); background: var(--ac-light); }
.pago-opt-icon { font-size: 24px; margin-bottom: 6px; }
.pago-opt-name { font-size: 13px; font-weight: 600; color: var(--ink); }
.pago-opt-sub { font-size: 11px; color: var(--ink3); margin-top: 2px; }
.pago-card-form { background: var(--bg); border: 1.5px solid var(--line); border-radius: var(--r); padding: 16px; margin-top: 14px; display: none; }
.pago-card-form.visible { display: block; }
.card-strip { display: flex; gap: 8px; align-items: center; margin-bottom: 12px; }
.card-logo { font-size: 10px; font-weight: 700; letter-spacing: .5px; padding: 3px 8px; border-radius: 4px; border: 1px solid var(--line); }
.card-logo.visa { color: #1a1f71; background: #e8eaf6; }
.card-logo.mc { color: #eb001b; background: #fce4ec; }
.card-logo.amex { color: #006fcf; background: #e3f2fd; }
.total-box { background: linear-gradient(135deg, var(--ac), var(--ac-dark)); border-radius: var(--r); padding: 16px; margin-top: 16px; display: flex; justify-content: space-between; align-items: center; }
.total-lbl { font-size: 13px; font-weight: 600; color: rgba(255,255,255,.8); }
.total-val { font-family: 'DM Mono', monospace; font-size: 22px; font-weight: 500; color: #fff; }
.modal-footer { padding: 16px 24px 24px; border-top: 1px solid var(--line); display: flex; gap: 10px; }
.btn-back { flex: 1; padding: 12px; border-radius: var(--r-sm); border: 1.5px solid var(--line); background: var(--white); color: var(--ink2); font-size: 14px; font-weight: 600; cursor: pointer; transition: all .15s; }
.btn-back:hover { border-color: var(--ac); color: var(--ac); }
.btn-next { flex: 2; padding: 12px; border-radius: var(--r-sm); border: none; background: var(--ac); color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; transition: all .2s; box-shadow: 0 4px 16px color-mix(in srgb, var(--ac) 35%, transparent); }
.btn-next:hover { background: var(--ac-dark); box-shadow: 0 6px 24px color-mix(in srgb, var(--ac) 45%, transparent); transform: translateY(-1px); }
.btn-next:disabled { opacity: .4; cursor: not-allowed; transform: none; }
.exito-wrap { text-align: center; padding: 40px 24px 32px; }
.exito-circle { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #22c55e, #16a34a); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 36px; box-shadow: 0 8px 32px rgba(34,197,94,.35); animation: popIn .4s cubic-bezier(.4,0,.2,1) .1s both; }
@keyframes popIn { from{opacity:0;transform:scale(.6)} to{opacity:1;transform:scale(1)} }
.exito-title { font-family: 'Syne', sans-serif; font-size: 24px; font-weight: 800; color: var(--ink); letter-spacing: -0.5px; margin-bottom: 8px; }
.exito-sub { font-size: 14px; color: var(--ink2); line-height: 1.7; margin-bottom: 24px; }
.exito-card { background: var(--bg); border-radius: var(--r); padding: 16px; text-align: left; border: 1px solid var(--line); margin-bottom: 20px; }
.exito-card-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; border-bottom: 1px solid var(--line); }
.ecr-lbl { color: var(--ink3); }
.ecr-val { font-weight: 600; color: var(--ink); }
.exito-ref { font-family: 'DM Mono', monospace; font-size: 11px; color: var(--ink3); margin-top: 8px; }
.btn-nueva-cita { display: inline-flex; align-items: center; gap: 8px; background: var(--ac); color: #fff; padding: 12px 28px; border-radius: var(--r-sm); font-size: 14px; font-weight: 700; border: none; cursor: pointer; transition: all .2s; box-shadow: var(--shadow-ac); }
.btn-nueva-cita:hover { background: var(--ac-dark); transform: translateY(-1px); }
.trust-section { padding: 48px 0 72px; }
.trust-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; margin-top: 40px; }
@media(max-width:640px) { .trust-grid { grid-template-columns: 1fr; } }
.trust-card { background: var(--white); border: 1px solid var(--line); border-radius: var(--r-lg); padding: 24px; box-shadow: var(--shadow-sm); }
.trust-icon { width: 44px; height: 44px; border-radius: 12px; background: var(--ac-light); border: 1px solid var(--ac-mid); display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 14px; }
.trust-title { font-size: 15px; font-weight: 700; color: var(--ink); margin-bottom: 6px; }
.trust-text  { font-size: 13px; color: var(--ink3); line-height: 1.6; }
.footer { background: var(--ink); color: rgba(255,255,255,.5); padding: 32px 24px; text-align: center; font-size: 12.5px; }
.footer a { color: rgba(255,255,255,.35); text-decoration: none; }
.footer a:hover { color: rgba(255,255,255,.6); }
.footer-brand { font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 8px; }
.toast-wrap { position: fixed; top: 80px; right: 20px; z-index: 600; display: flex; flex-direction: column; gap: 8px; pointer-events: none; }
.toast { background: var(--ink); color: #fff; padding: 12px 18px; border-radius: var(--r); font-size: 13px; font-weight: 500; box-shadow: var(--shadow-lg); animation: toastIn .25s ease both, toastOut .25s ease 2.5s both; border-left: 3px solid var(--ac); }
.toast.err { border-left-color: #ef4444; }
@keyframes toastIn  { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:translateX(0)} }
@keyframes toastOut { from{opacity:1;transform:translateX(0)} to{opacity:0;transform:translateX(20px)} }
</style>
</head>
<body>

<nav class="nav">
  <a class="nav-logo-wrap" href="/agenda">
    <div class="nav-logo">{{ strtoupper(substr($tenantNombre, 0, 1)) }}</div>
    <div>
      <div class="nav-name">{{ $tenantNombre }}</div>
      <div class="nav-tagline">Sistema de Salud</div>
    </div>
  </a>
  <div class="nav-pills">
    <a href="#especialidades" class="nav-pill">Especialidades</a>
    <a href="/agenda" class="nav-cta">Reservar hora →</a>
    <a href="/auth/login/web" class="nav-pill" style="color:var(--ink3);font-size:12px;">Staff</a>
  </div>
</nav>

<section class="hero">
  <div class="hero-inner">
    <div>
      <div class="hero-badge">
        <span class="hero-badge-dot"></span>
        Reserva online disponible 24/7
      </div>
      <h1 class="hero-h1">
        Tu salud,<br>
        <em>sin esperas</em><br>
        ni llamadas.
      </h1>
      <p class="hero-p">
        {{ $config->descripcion_landing ?? 'Elige tu especialidad, tu médico y el horario que más te acomoda. Paga en línea y listo — tu cita queda confirmada al instante.' }}
      </p>
      <div class="hero-badges">
        <span class="hbadge">✓ Confirmación inmediata</span>
        <span class="hbadge">✓ Pago seguro en línea</span>
        <span class="hbadge">✓ Recordatorio por WhatsApp</span>
      </div>
      <a href="#especialidades" class="hero-cta" onclick="document.getElementById('especialidades').scrollIntoView({behavior:'smooth'});return false;">
        Ver especialidades disponibles
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </a>
    </div>

    <div class="hero-avail-card" id="heroAvailCard">
      <div class="hac-title">Próximas horas disponibles · Hoy</div>
      <div class="hac-list" id="hacList">
        <div style="text-align:center;padding:20px;color:var(--ink3);font-size:13px;">
          <div class="spinner" style="margin:0 auto 8px;"></div>
          Cargando disponibilidad…
        </div>
      </div>
    </div>
  </div>
</section>

<div class="site-main">
  <section class="especialidades-section" id="especialidades">
    <div class="sec-label">Nuestros servicios</div>
    <div class="sec-title">¿Qué especialidad necesitas?</div>
    <div class="sec-sub">Selecciona el área de atención y te mostramos los médicos disponibles con sus próximas horas libres.</div>

    <div class="esp-grid" id="espGrid">
      <div style="grid-column:1/-1;padding:40px;text-align:center;color:var(--ink3);">
        <div class="spinner" style="margin:0 auto 12px;"></div>
        Cargando especialidades…
      </div>
    </div>
  </section>

  <div class="agenda-panel-wrap" id="agendaPanel">
    <div class="ap-header">
      <div class="ap-header-left">
        <div class="ap-icon" id="apIcon">🏥</div>
        <div>
          <div class="ap-esp-nombre" id="apEspNombre">Especialidad</div>
          <div class="ap-esp-sub" id="apEspSub">Selecciona un médico y horario</div>
        </div>
      </div>
      <div class="ap-fecha-nav">
        <button class="ap-fecha-btn" onclick="cambiarSemana(-1)">‹</button>
        <div class="ap-fecha-display" id="apFechaDisplay"></div>
        <button class="ap-fecha-btn" onclick="cambiarSemana(1)">›</button>
        <button class="ap-hoy-btn" onclick="irHoy()">Hoy</button>
      </div>
    </div>

    <div class="ap-semana" id="apSemana"></div>

    <div style="padding:0 16px;">
      <button class="ap-todos-btn activo" id="btnTodos" onclick="toggleVistaTodos()">
        Todos los médicos
      </button>
    </div>

    <div class="ap-body" id="apBody">
      <div class="ap-loading">
        <div class="spinner"></div> Cargando disponibilidad…
      </div>
    </div>
  </div>
</div>

  <section class="trust-section">
    <div class="sec-label">¿Por qué nosotros?</div>
    <div class="sec-title">Simple, seguro y sin esperas</div>
    <div class="trust-grid">
      <div class="trust-card">
        <div class="trust-icon">🔒</div>
        <div class="trust-title">Pago 100% seguro</div>
        <div class="trust-text">Transacción encriptada con certificado SSL. Aceptamos Webpay, tarjetas de crédito y débito.</div>
      </div>
      <div class="trust-card">
        <div class="trust-icon">📱</div>
        <div class="trust-title">Recordatorio automático</div>
        <div class="trust-text">Te enviamos un recordatorio por WhatsApp 24 horas antes de tu consulta para que no la olvides.</div>
      </div>
      <div class="trust-card">
        <div class="trust-icon">⚡</div>
        <div class="trust-title">Confirmación inmediata</div>
        <div class="trust-text">Tu hora queda reservada al instante. Recibes comprobante por correo con todos los detalles.</div>
      </div>
    </div>
  </section>

<footer class="footer">
  <div class="footer-brand">{{ $tenantNombre }}</div>
  <p>Reservas en línea · Acceso staff <a href="/auth/login/web">→ Ingresar</a></p>
  <p style="margin-top:6px;">Powered by <a href="https://benderand.cl" target="_blank">BenderAnd</a></p>
</footer>

<div class="modal-overlay" id="modalOverlay">
  <div class="modal-box" id="modalBox">

    <div class="modal-header">
      <div>
        <div class="modal-title" id="modalTitle">Reservar consulta</div>
        <div class="modal-sub" id="modalSub"></div>
      </div>
      <button class="modal-close" onclick="cerrarModal()">✕</button>
    </div>

    <div class="modal-body">
      <div class="modal-steps">
        <div class="ms-step active" id="mst1">
          <div class="ms-num">1</div><div class="ms-lbl">Datos</div>
        </div>
        <div class="ms-step" id="mst2">
          <div class="ms-num">2</div><div class="ms-lbl">Pago</div>
        </div>
        <div class="ms-step" id="mst3">
          <div class="ms-num">3</div><div class="ms-lbl">Confirmación</div>
        </div>
      </div>

      <div class="resumen-cita" id="resumenCita">
        <div class="rc-item">
          <div class="rc-label">Médico</div>
          <div class="rc-val" id="rcMedico">—</div>
        </div>
        <div class="rc-item">
          <div class="rc-label">Especialidad</div>
          <div class="rc-val" id="rcEsp">—</div>
        </div>
        <div class="rc-item">
          <div class="rc-label">Fecha</div>
          <div class="rc-val" id="rcFecha">—</div>
        </div>
        <div class="rc-item">
          <div class="rc-label">Hora</div>
          <div class="rc-val" id="rcHora" style="font-family:'DM Mono',monospace;">—</div>
        </div>
        <div class="rc-item">
          <div class="rc-label">Servicio</div>
          <div class="rc-val" id="rcServicio">—</div>
        </div>
        <div class="rc-item">
          <div class="rc-label">Valor</div>
          <div class="rc-val money" id="rcValor">—</div>
        </div>
      </div>

      <div id="mpaso1">
        <div class="form-grid">
          <div class="form-field">
            <label class="form-label">Nombre *</label>
            <input type="text" class="form-input" id="fNombre" placeholder="Tu nombre">
          </div>
          <div class="form-field">
            <label class="form-label">Apellido *</label>
            <input type="text" class="form-input" id="fApellido" placeholder="Tu apellido">
          </div>
        </div>
        <div class="form-grid">
          <div class="form-field">
            <label class="form-label">RUT</label>
            <input type="text" class="form-input" id="fRut" placeholder="12.345.678-9">
          </div>
          <div class="form-field">
            <label class="form-label">Teléfono *</label>
            <input type="tel" class="form-input" id="fTelefono" placeholder="+56 9 1234 5678">
          </div>
        </div>
        <div class="form-field">
          <label class="form-label">Email</label>
          <input type="email" class="form-input" id="fEmail" placeholder="tu@correo.cl">
        </div>
        <div class="form-field">
          <label class="form-label">Motivo de consulta</label>
          <textarea class="form-input form-textarea" id="fMotivo" placeholder="Breve descripción de lo que necesitas..."></textarea>
        </div>
      </div>

      <div id="mpaso2" style="display:none;">
        <div class="pago-metodos">
          <div class="pago-opt sel" data-metodo="webpay" onclick="selMetodo(this)">
            <div class="pago-opt-icon">🏦</div>
            <div class="pago-opt-name">Webpay</div>
            <div class="pago-opt-sub">Débito / Crédito</div>
          </div>
          <div class="pago-opt" data-metodo="tarjeta" onclick="selMetodo(this)">
            <div class="pago-opt-icon">💳</div>
            <div class="pago-opt-name">Tarjeta</div>
            <div class="pago-opt-sub">Visa · MC · Amex</div>
          </div>
          <div class="pago-opt" data-metodo="transferencia" onclick="selMetodo(this)">
            <div class="pago-opt-icon">🔁</div>
            <div class="pago-opt-name">Transferencia</div>
            <div class="pago-opt-sub">Pago al banco</div>
          </div>
          <div class="pago-opt" data-metodo="presencial" onclick="selMetodo(this)">
            <div class="pago-opt-icon">🏥</div>
            <div class="pago-opt-name">En clínica</div>
            <div class="pago-opt-sub">Pagar al llegar</div>
          </div>
        </div>

        <div class="pago-card-form" id="pagoCardForm">
          <div class="card-strip">
            <div class="card-logo visa">VISA</div>
            <div class="card-logo mc">MC</div>
            <div class="card-logo amex">AMEX</div>
          </div>
          <div class="form-field">
            <label class="form-label">Número de tarjeta</label>
            <input type="text" class="form-input" id="fCard" placeholder="1234 5678 9012 3456"
                   maxlength="19" oninput="formatCard(this)">
          </div>
          <div class="form-grid">
            <div class="form-field">
              <label class="form-label">Vencimiento</label>
              <input type="text" class="form-input" id="fExpiry" placeholder="MM/AA" maxlength="5" oninput="formatExpiry(this)">
            </div>
            <div class="form-field">
              <label class="form-label">CVV</label>
              <input type="text" class="form-input" id="fCvv" placeholder="123" maxlength="4">
            </div>
          </div>
          <div class="form-field">
            <label class="form-label">Nombre en la tarjeta</label>
            <input type="text" class="form-input" id="fCardName" placeholder="NOMBRE APELLIDO">
          </div>
        </div>

        <div class="pago-card-form visible" id="pagoTransfForm" style="display:none;">
          <div style="font-size:13px;line-height:1.8;color:var(--ink2);">
            <div style="margin-bottom:8px;font-weight:700;color:var(--ink);">Datos para transferencia:</div>
            <div><b>Banco:</b> Scotiabank</div>
            <div><b>Cuenta corriente:</b> 123-456-789</div>
            <div><b>RUT:</b> {{ tenant('rut_empresa') ?? '76.000.000-1' }}</div>
            <div style="margin-top:10px;padding:8px 12px;background:var(--ac-light);border-radius:8px;font-family:'DM Mono',monospace;font-size:12px;color:var(--ac-dark);">
              Glosa: <b>CITA-<span id="transfRef">—</span></b>
            </div>
            <div style="margin-top:8px;font-size:12px;color:var(--ink3);">Envía el comprobante al correo de la clínica. Tu reserva queda pendiente hasta confirmar el pago.</div>
          </div>
        </div>

        <div class="total-box">
          <div class="total-lbl">Total a pagar</div>
          <div class="total-val" id="totalVal">$0</div>
        </div>
      </div>

      <div id="mpaso3" style="display:none;"></div>

    </div>

    <div class="modal-footer" id="modalFooter">
      <button class="btn-back" id="btnBack" onclick="modalVolver()" style="display:none;">← Volver</button>
      <button class="btn-next" id="btnNext" onclick="modalSiguiente()">Continuar →</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast-wrap" id="toastWrap"></div>

<script>
// ==========================================
// ESTADO GLOBAL Y DATA DE BLADE
// ==========================================
const RECURSOS = @json($recursos);
const TENANT_NOMBRE = "{{ $tenantNombre }}";
const API_SLOTS = '/api/public/agenda/slots';
const API_CITA  = '/api/public/agenda/cita';

let estado = {
  espId: null,      // Especialidad seleccionada
  recursoId: null,  // Médico seleccionado (null = todos los de la esp)
  medicosSel: [],   // Lista de médicos filtrados por especialidad
  fechaBase: new Date(), // Semana actual visible
  diaSel: null,     // ISO 'YYYY-MM-DD' del día clickeado
  slotSel: null,    // Objeto con data del slot elegido
  slotsCache: {},   // { '2026-03-26': [slots] }
  modalPaso: 1,     // 1: Datos, 2: Pago, 3: Éxito
  loadingEsp: false,
  loadingSlots: false,
};

// ==========================================
// UTILIDADES
// ==========================================
function toISO(d) {
  const tr = new Date(d.getTime() - d.getTimezoneOffset()*60000);
  return tr.toISOString().split('T')[0];
}
function addDays(d, days) {
  const cd = new Date(d); cd.setDate(cd.getDate() + days); return cd;
}
function fmtFecha(iso) {
  if(!iso) return '';
  const d = new Date(iso+'T12:00:00');
  const dStr = d.toLocaleDateString('es-CL', { weekday:'long', day:'numeric', month:'short' });
  return dStr.charAt(0).toUpperCase() + dStr.slice(1);
}
function fmtHora(str) { return str.substring(0,5); }
function formatMoney(n) { return '$' + new Intl.NumberFormat('es-CL').format(n); }
function toast(msg, isErr=false) {
  const wrap = document.getElementById('toastWrap');
  const el = document.createElement('div');
  el.className = 'toast' + (isErr?' err':'');
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(() => el.remove(), 3000);
}

// FORMATTERS TARJETA
function formatCard(el) {
  let v = el.value.replace(/\D/g, '');
  v = v.replace(/(.{4})/g, '$1 ').trim();
  el.value = v;
}
function formatExpiry(el) {
  let v = el.value.replace(/\D/g, '');
  if(v.length > 2) v = v.substring(0,2) + '/' + v.substring(2,4);
  el.value = v;
}

// ==========================================
// DOM ELEMENTS
// ==========================================
const elEspGrid = document.getElementById('espGrid');
const elPanel = document.getElementById('agendaPanel');
const elSemana = document.getElementById('apSemana');
const elBody = document.getElementById('apBody');
const elEspNombre = document.getElementById('apEspNombre');
const elEspSub = document.getElementById('apEspSub');
const elFechaNav = document.getElementById('apFechaDisplay');
const btnTodos = document.getElementById('btnTodos');

// ==========================================
// INIT (MÓDULOS)
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
  initEspecialidades();
  initSemana();
  cargarDisponibilidadHero();
});

// ==========================================
// MÓDULO: ESPECIALIDADES
// ==========================================
function initEspecialidades() {
  const agrp = {};
  RECURSOS.forEach(r => {
    const esp = r.especialidad || 'General';
    if(!agrp[esp]) agrp[esp] = { n:0, icon:'🩺', docIds: new Set() };
    agrp[esp].n++;
    agrp[esp].docIds.add(r.id);
  });

  const mEsp = {
    'Odontología': '🦷', 'Kinesiología': '🦴', 'Psicología': '🧠',
    'Nutrición': '🥗', 'Cardiología': '🫀', 'Pediatría': '🧸'
  };

  elEspGrid.innerHTML = '';
  Object.keys(agrp).sort().forEach(espName => {
    const data = agrp[espName];
    const docs = Array.from(data.docIds).map(id => RECURSOS.find(x => x.id === id));
    
    const div = document.createElement('div');
    div.className = 'esp-card';
    div.onclick = () => selEspecialidad(div, espName, docs);
    div.innerHTML = `
      <div class="esp-icon">${mEsp[espName] || data.icon}</div>
      <div class="esp-nombre">${espName}</div>
      <div class="esp-desc">Evaluación, diagnóstico y tratamiento.</div>
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <span class="esp-count">${data.docIds.size} ${data.docIds.size===1?'médico':'médicos'}</span>
        <div class="esp-arrow">→</div>
      </div>
    `;
    elEspGrid.appendChild(div);
  });
}

function selEspecialidad(cardEl, espName, docs) {
  document.querySelectorAll('.esp-card').forEach(el => el.classList.remove('activa'));
  cardEl.classList.add('activa');
  
  estado.espId = espName;
  estado.medicosSel = docs;
  estado.recursoId = null; // reset medico
  estado.diaSel = toISO(new Date());

  elEspNombre.textContent = espName;
  elEspSub.textContent = `Selecciona tu horario prestador favorito`;
  elPanel.classList.add('visible');
  
  btnTodos.classList.add('activo');
  document.getElementById('apIcon').textContent = cardEl.querySelector('.esp-icon').textContent;
  
  // scroll al panel
  setTimeout(() => elPanel.scrollIntoView({behavior:'smooth', block:'start'}), 100);

  cargarSlotsDia(estado.diaSel);
}

// ==========================================
// MÓDULO: SEMANA / CALENDARIO
// ==========================================
function initSemana() { renderSemana(); }
function cambiarSemana(inc) { estado.fechaBase = addDays(estado.fechaBase, inc * 7); renderSemana(); }
function irHoy() { estado.fechaBase = new Date(); estado.diaSel = toISO(estado.fechaBase); renderSemana(); cargarSlotsDia(estado.diaSel); }

function renderSemana() {
  const initStr = fmtFecha(toISO(estado.fechaBase));
  const lDom = addDays(estado.fechaBase, 6);
  elFechaNav.textContent = initStr + ' — ' + fmtFecha(toISO(lDom)).split(' ')[1] + ' ' + fmtFecha(toISO(lDom)).split(' ')[2];
  
  elSemana.innerHTML = '';
  let d = new Date(estado.fechaBase);
  // Empujar a Lunes si es necesario (simplificado: muestra 7 dias desde el base)
  for(let i=0; i<7; i++) {
    const iso = toISO(d);
    const dStr = d.toLocaleDateString('es-CL', { weekday:'short' });
    const nStr = d.getDate();
    const isHoy = iso === toISO(new Date());
    const isSel = iso === estado.diaSel;

    const div = document.createElement('div');
    div.className = `ap-dia-col ${isHoy?'hoy':''} ${isSel?'sel':''}`;
    div.onclick = () => {
      estado.diaSel = iso;
      renderSemana();
      cargarSlotsDia(iso);
    };
    div.innerHTML = `
      <div class="ap-dia-lbl">${dStr.substring(0,3)}</div>
      <div class="ap-dia-num">${nStr}</div>
      <div class="ap-dia-dots" id="dots-${iso}"></div>
    `;
    elSemana.appendChild(div);
    d = addDays(d, 1);
  }
}

// ==========================================
// MÓDULO: DISPONIBILIDAD Y SLOTS
// ==========================================
async function fetchPublic(url) {
  try {
    const res = await fetch(url);
    if(!res.ok) throw new Error('Network err');
    return await res.json();
  } catch(e) {
    console.error(e); return null;
  }
}

async function cargarDisponibilidadHero() {
  const hoyIso = toISO(new Date());
  const wrap = document.getElementById('hacList');
  // Carga los primeros disponibles de hoy para cualquier recurso
  let html = '';
  // Usamos un param limite falso o se filtra visualmente
  const res = await fetchPublic(`${API_SLOTS}?fecha_inicio=${hoyIso}&fecha_fin=${hoyIso}`);
  if(res && res.data) {
    let cuenta = 0;
    Object.keys(res.data).forEach(idDoc => {
      const doc = RECURSOS.find(r => r.id == idDoc);
      const daySlots = res.data[idDoc][hoyIso] || [];
      if(doc && daySlots.length > 0 && cuenta < 3) {
        cuenta++;
        const sHtml = daySlots.slice(0,3).map(s => `<span class="hac-slot">${fmtHora(s.hora_inicio)}</span>`).join('');
        html += `
          <div class="hac-row" onclick="document.getElementById('especialidades').scrollIntoView({behavior:'smooth'})">
            <div class="hac-dot" style="background:${doc.color||'#e2e8f0'}">${doc.nombre.charAt(0)}</div>
            <div class="hac-info">
              <div class="hac-name">${doc.nombre}</div>
              <div class="hac-esp">Disponibilidad hoy</div>
              <div class="hac-slots">${sHtml} <span class="hac-badge-avail">+${daySlots.length>3?daySlots.length-3:0}</span></div>
            </div>
          </div>
        `;
      }
    });
    if(html) { wrap.innerHTML = html; } 
    else { wrap.innerHTML = '<div style="font-size:12px;color:var(--ink3);text-align:center;padding:10px;">No hay horas disponibles para hoy. Revisa el calendario.</div>'; }
  } else {
    wrap.innerHTML = '<div style="font-size:12px;color:var(--ink3);text-align:center;padding:10px;">Error al cargar disponibilidad</div>';
  }
}

async function cargarSlotsDia(iso) {
  if(!iso) return;
  elBody.innerHTML = `<div class="ap-loading"><div class="spinner"></div> Buscando en los calendarios...</div>`;
  
  if(!estado.slotsCache[iso]) {
    // Si no está en cache, busqueda a API por todo el rango de la especialidad (optimizacion: traemos solo ese dia de todos)
    const url = `${API_SLOTS}?fecha_inicio=${iso}&fecha_fin=${iso}`;
    const res = await fetchPublic(url);
    
    if(res && res.data) {
      estado.slotsCache[iso] = res.data;
    } else {
      estado.slotsCache[iso] = {}; // empty
    }
  }
  
  // Procesar cache y medicos seleccionados
  const cacheDia = estado.slotsCache[iso];
  
  // Sidebar (Médicos)
  let sbHtml = '<div class="ap-body-sidebar">';
  estado.medicosSel.forEach(doc => {
    const cSlots = cacheDia[doc.id] && cacheDia[doc.id][iso] ? cacheDia[doc.id][iso] : [];
    const isAct = estado.recursoId == doc.id;
    sbHtml += `
      <div class="ap-doc-item ${isAct?'sel':''}" onclick="selMedico(${doc.id})">
        <div class="ap-doc-avatar" style="background:${doc.color||'#f1f5f9'}; border:1px solid rgba(0,0,0,0.1)">${doc.nombre.charAt(0)}</div>
        <div>
          <div class="ap-doc-nombre">${doc.nombre}</div>
          <div class="ap-doc-slots">${cSlots.length} horas disponibles</div>
        </div>
      </div>
    `;
    
    // update dots en calendario (si tiene > 0 pongo un punto) - TODO visual sync
    const elDot = document.getElementById(`dots-${iso}`);
    if(elDot && cSlots.length > 0) { elDot.innerHTML = '<div class="ap-dia-dot"></div>'; }
  });
  sbHtml += '</div>';

  // Area Slots (Cuerpo)
  let bdHtml = '<div class="ap-body-slots">';
  let hayCualquiera = false;
  
  estado.medicosSel.forEach((doc, idx) => {
    const isVis = estado.recursoId == null || estado.recursoId == doc.id;
    const cSlots = cacheDia[doc.id] && cacheDia[doc.id][iso] ? cacheDia[doc.id][iso] : [];
    
    if(isVis) {
      hayCualquiera = true;
      let slotsMarkup = '';
      if(cSlots.length > 0) {
        cSlots.forEach(s => {
          const srv = doc.servicios && doc.servicios.length > 0 ? doc.servicios[0] : null;
          const valor = srv ? srv.precio : 0;
          const srvNombre = srv ? srv.nombre : 'Consulta Médica';
          const sObjStr = encodeURIComponent(JSON.stringify({ 
            docName: doc.nombre, docId: doc.id,
            horaIni: s.hora_inicio, horaFin: s.hora_fin,
            fecha: iso, valor: valor, srv: srvNombre, srvId: srv?srv.id:null
          }));
          slotsMarkup += `
            <div class="sf-slot" onclick="abrirModalCheckout('${sObjStr}')">
              <div class="sf-slot-dot"></div> ${fmtHora(s.hora_inicio)}
            </div>
          `;
        });
      } else {
        slotsMarkup = `<div class="sf-empty">No hay disponibilidad para este día. Prueba otro.</div>`;
      }

      bdHtml += `
        <div class="slots-fila ${isVis?'visible':''}" id="fila-doc-${doc.id}">
          <div class="sf-header">
            <div class="sf-avatar" style="background:${doc.color||'#f1f5f9'}">${doc.nombre.charAt(0)}</div>
            <div>
              <div class="sf-nombre">${doc.nombre}</div><div class="sf-esp">${estado.espId}</div>
            </div>
          </div>
          <div class="sf-slots-row">${slotsMarkup}</div>
        </div>
      `;
    }
  });

  if(!hayCualquiera) {
     bdHtml += `<div style="text-align:center;padding:40px;color:var(--ink3);">Selecciona un médico del listado</div>`;
  }
  
  bdHtml += '</div>';
  elBody.innerHTML = sbHtml + bdHtml;
}

function selMedico(id) {
  estado.recursoId = id;
  btnTodos.classList.remove('activo');
  cargarSlotsDia(estado.diaSel);
}
function toggleVistaTodos() {
  estado.recursoId = null;
  btnTodos.classList.add('activo');
  cargarSlotsDia(estado.diaSel);
}

// ==========================================
// MÓDULO: MODAL Y CHECKOUT (SIN AUTH)
// ==========================================
let pPagoSel = 'webpay';

function abrirModalCheckout(strData) {
  const d = JSON.parse(decodeURIComponent(strData));
  estado.slotSel = d;
  
  // Rellenar resumen
  document.getElementById('rcMedico').textContent = d.docName;
  document.getElementById('rcEsp').textContent = estado.espId;
  document.getElementById('rcFecha').textContent = fmtFecha(d.fecha);
  document.getElementById('rcHora').textContent = fmtHora(d.horaIni);
  document.getElementById('rcServicio').textContent = d.srv;
  
  const v = Math.round(d.valor);
  document.getElementById('rcValor').textContent = v > 0 ? formatMoney(v) : 'Por confirmar';
  document.getElementById('totalVal').textContent = v > 0 ? formatMoney(v) : 'Por confirmar';
  
  estado.modalPaso = 1;
  renderPasoModal();
  document.getElementById('modalOverlay').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  estado.slotSel = null;
}

function renderPasoModal() {
  const btnNext = document.getElementById('btnNext');
  const btnBack = document.getElementById('btnBack');
  
  [1,2,3].forEach(i => {
    const elId = 'mst'+i;
    const cntId = 'mpaso'+i;
    const stEl = document.getElementById(elId);
    const cnEl = document.getElementById(cntId);
    
    if(i < estado.modalPaso) {
      stEl.className = 'ms-step done';
      if(cnEl) cnEl.style.display = 'none';
    } else if (i === estado.modalPaso) {
      stEl.className = 'ms-step active';
      if(cnEl) cnEl.style.display = 'block';
    } else {
      stEl.className = 'ms-step';
      if(cnEl) cnEl.style.display = 'none';
    }
  });

  if(estado.modalPaso === 1) {
    btnBack.style.display = 'none';
    btnNext.textContent = 'Ir al pago →';
    btnNext.style.display = 'block';
  } else if(estado.modalPaso === 2) {
    btnBack.style.display = 'block';
    btnNext.textContent = 'Confirmar y Pagar';
    btnNext.style.display = 'block';
  } else if(estado.modalPaso === 3) {
    document.getElementById('modalFooter').style.display = 'none';
    document.getElementById('resumenCita').style.display = 'none';
    document.getElementById('modalSub').textContent = 'Tu reserva está lista';
  }
}

function modalVolver() {
  if(estado.modalPaso > 1) { estado.modalPaso--; renderPasoModal(); }
}

async function modalSiguiente() {
  if(estado.modalPaso === 1) {
    // Validar Datos
    const nm = document.getElementById('fNombre').value.trim();
    const ap = document.getElementById('fApellido').value.trim();
    const tl = document.getElementById('fTelefono').value.trim();
    const em = document.getElementById('fEmail').value.trim();
    if(!nm || !ap || !tl) { toast('Completa los campos obligatorios (*)', true); return; }
    
    estado.modalPaso = 2; renderPasoModal();
  } 
  else if(estado.modalPaso === 2) {
    // Fake procesar pago y crear cita
    const btn = document.getElementById('btnNext');
    btn.disabled = true; btn.textContent = 'Procesando...';
    
    // POST al Backend Público
    const pData = {
      agenda_recurso_id: estado.slotSel.docId,
      servicio_id: estado.slotSel.srvId,
      paciente_nombre: document.getElementById('fNombre').value.trim() + ' ' + document.getElementById('fApellido').value.trim(),
      paciente_telefono: document.getElementById('fTelefono').value.trim(),
      paciente_email: document.getElementById('fEmail').value.trim(),
      paciente_rut: document.getElementById('fRut').value.trim(),
      fecha: estado.slotSel.fecha,
      hora_inicio: estado.slotSel.horaIni,
      hora_fin: estado.slotSel.horaFin,
      notas_internas: document.getElementById('fMotivo').value.trim(),
      metodo_pago: pPagoSel // 'webpay', 'tarjeta', 'transferencia', 'presencial'
    };

    try {
      const res = await fetch(API_CITA, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify(pData)
      });
      
      const rj = await res.json();
      if(!res.ok) throw new Error(rj.message || 'Error al agendar');
      
      // Mostrar Éxito
      mostrarExito(rj.cita || {});
    } catch(e) {
      toast(e.message, true);
      btn.disabled = false; btn.textContent = 'Confirmar y Pagar';
    }
  }
}

function selMetodo(el) {
  document.querySelectorAll('.pago-opt').forEach(x => x.classList.remove('sel'));
  el.classList.add('sel');
  pPagoSel = el.dataset.metodo;
  
  const cf = document.getElementById('pagoCardForm');
  const tf = document.getElementById('pagoTransfForm');
  // Ocultar forms
  cf.classList.remove('visible'); setTimeout(()=>cf.style.display='none',150);
  tf.classList.remove('visible'); setTimeout(()=>tf.style.display='none',150);
  
  if(pPagoSel === 'tarjeta') {
    cf.style.display='block'; setTimeout(()=>cf.classList.add('visible'),10);
  } else if (pPagoSel === 'transferencia') {
    document.getElementById('transfRef').textContent = Math.floor(Math.random()*90000) + 10000;
    tf.style.display='block'; setTimeout(()=>tf.classList.add('visible'),10);
  }
}

function mostrarExito(citaData) {
  const cd = citaData;
  const ref = cd.id ? `RESERVA #${cd.id}` : `REF-${Math.floor(Math.random()*90000)+10000}`;
  
  estado.modalPaso = 3;
  renderPasoModal();
  
  const m3 = document.getElementById('mpaso3');
  m3.innerHTML = `
    <div class="exito-wrap">
      <div class="exito-circle">✓</div>
      <div class="exito-title">¡Hora confirmada!</div>
      <div class="exito-sub">Hemos enviado los detalles a <b>${document.getElementById('fEmail').value || 'tu correo'}</b>. Recuerda llegar 10 minutos antes.</div>
      
      <div class="exito-card">
        <div class="exito-card-row">
          <span class="ecr-lbl">Paciente</span>
          <span class="ecr-val">${document.getElementById('fNombre').value} ${document.getElementById('fApellido').value}</span>
        </div>
        <div class="exito-card-row">
          <span class="ecr-lbl">Médico</span>
          <span class="ecr-val">${estado.slotSel.docName}</span>
        </div>
        <div class="exito-card-row">
          <span class="ecr-lbl">Fecha y hora</span>
          <span class="ecr-val">${fmtFecha(estado.slotSel.fecha)}, ${fmtHora(estado.slotSel.horaIni)}</span>
        </div>
        <div class="exito-card-row" style="border:none;">
          <span class="ecr-lbl">Pago</span>
          <span class="ecr-val" style="color:var(--ac-dark)">${pPagoSel==='presencial'?'Paga en clínica':(pPagoSel==='transferencia'?'Pendiente transf.':'Pagado ('+pPagoSel+')')}</span>
        </div>
      </div>
      <div class="exito-ref">Comprobante: ${ref}</div>
      
      <div style="margin-top:32px;">
        <button class="btn-nueva-cita" onclick="window.location.reload()">
          ↵ Volver al inicio
        </button>
      </div>
    </div>
  `;
}
</script>
</body>
</html>
