# Refactor: Mover todo superadmin a central, conservar UI de superadmin

## Decisión de arquitectura

**Ya no existe `/superadmin/*` como panel separado.**
Todo vive en `/central/*`. La UI que tenía superadmin (diseño púrpura, IBM Plex Mono, sidebar con KPIs) se aplica a central. Las vistas de central que usan Tailwind/Inter se reemplazan o unifican con el diseño de superadmin.

---

## Qué se conserva (UI de superadmin)

El diseño de referencia es `specs/files/superadmin.html`. Sus características:

- Fondo `#08080a`, tipografía `IBM Plex Mono` + `IBM Plex Sans`
- Color accent púrpura `#e040fb`
- Topbar con brand `B&`, badge `BenderAnd`, avatar `SA`
- Sidebar de 220px con secciones, `.nav-item`, `.nav-item.active` con barra púrpura izquierda
- Layout: `body → topbar + layout(sidebar + content)`
- CSS variables: `--bg`, `--s1`, `--s2`, `--ac`, `--ok`, `--warn`, `--err`, `--mono`, `--sans`

---

## Rutas finales (todo en /central)

| Página | URL nueva | Antes |
|---|---|---|
| Login | `GET /central/login` | igual |
| Logout | `POST /central/logout` | igual |
| Dashboard | `GET /central` | `/superadmin` |
| Tenants | `GET /central/tenants` | igual |
| Billing | `GET /central/billing` | igual |
| Spider QA | `GET /central/spider` | `/superadmin/spider` |

Eliminar o redirigir con `301` todas las rutas `/superadmin/*` a su equivalente en `/central/*`.

---

## Menú lateral de central (nuevo)

Reemplazar el menú Tailwind/Inter actual de central por el menú de superadmin:

```html
<div class="nav-section">
  <div class="nav-section-lbl">Principal</div>
  <a href="/central" class="nav-item {{ request()->is('central') ? 'active' : '' }}">
    <span class="nav-icon">◈</span> Dashboard
  </a>
  <a href="/central/tenants" class="nav-item {{ request()->is('central/tenants*') ? 'active' : '' }}">
    <span class="nav-icon">⊞</span> Tenants
  </a>
  <a href="/central/billing" class="nav-item {{ request()->is('central/billing*') ? 'active' : '' }}">
    <span class="nav-icon">⌘</span> Billing
  </a>
  <a href="#" class="nav-item">
    <span class="nav-icon">🗂</span> Logs / Auditoría
  </a>
  <a href="/central/spider" class="nav-item {{ request()->is('central/spider*') ? 'active' : '' }}">
    <span class="nav-icon">🕸</span> Spider QA
  </a>
</div>
```

---

## Layout de central a reemplazar

El archivo `resources/views/layouts/central.blade.php` debe adoptar el HTML/CSS de superadmin completo. Reemplazar el contenido actual por la estructura de superadmin:

```html
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'SuperAdmin') - BenderAnd</title>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* --- pegar aquí el CSS completo de superadmin.html --- */
    /* incluyendo: reset, :root variables, topbar, layout, sidebar, nav-item,
       content, card, kpi-row, kpi, page, page-hdr, page-title,
       overlay, login-card, form-group, form-input, btn, tbl, badge */
  </style>
  @stack('styles')
</head>
<body>
  <header class="topbar">
    <span class="tb-brand">B&</span>
    <div class="tb-sep"></div>
    <span class="tb-title">@yield('page-title', 'Super Admin')</span>
    <div class="tb-badge">BenderAnd</div>
    <div class="tb-avatar">SA</div>
  </header>

  <div class="layout">
    <aside class="sidebar">
      <!-- menú con links a /central/* (ver sección anterior) -->
      @include('central.partials.sidebar')
    </aside>
    <main class="content">
      @yield('content')
    </main>
  </div>

  @stack('scripts')
</body>
</html>
```

---

## Vistas a actualizar

| Vista | Cambio |
|---|---|
| `layouts/central.blade.php` | Reemplazar con UI de superadmin (ver arriba) |
| `central/dashboard.blade.php` | Extender layout central, contenido del dashboard de superadmin (KPIs + MRR chart), sin iframe de spider |
| `central/tenants/index.blade.php` | Extender layout central con UI de superadmin (ya funciona, solo cambiar layout base) |
| `central/billing.blade.php` | Extender layout central con UI de superadmin |
| `central/spider.blade.php` | Vista nueva: extender layout central, contenido completo del Spider QA (el HTML de spider de superadmin.html, sección `spider-layout`) |
| `central/partials/sidebar.blade.php` | Partial nuevo con el menú de 5 ítems (ver sección anterior) |

---

## CentralAuthController — redirect post-login

```php
// Después de autenticar con guard super_admin:
return redirect()->intended('/central');
```

Verificar que la ruta `intended` por defecto es `/central` y no `/superadmin`.

---

## routes/web.php — limpiar rutas superadmin

```php
// Eliminar o comentar el grupo superadmin:
// Route::prefix('superadmin')->group(...)

// Agregar redirects 301 para no romper bookmarks:
Route::redirect('/superadmin', '/central', 301);
Route::redirect('/superadmin/tenants', '/central/tenants', 301);
Route::redirect('/superadmin/billing', '/central/billing', 301);
Route::redirect('/superadmin/spider', '/central/spider', 301);
Route::redirect('/superadmin/login', '/central/login', 301);

// Agregar ruta spider dentro del grupo central existente:
Route::get('/spider', [SpiderController::class, 'index'])->name('central.spider');
```

---

## CSS crítico que debe estar en el layout

Asegurarse de que el layout central incluye estas clases que usaban las vistas de superadmin:

```css
.kpi-row { display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
.kpi { background:var(--s2); border:1px solid var(--b1); border-radius:12px; padding:20px; flex:1; min-width:140px; }
.kpi-lbl { font-size:11px; color:var(--t2); text-transform:uppercase; letter-spacing:1px; margin-bottom:8px; font-family:var(--mono); }
.kpi-val { font-family:var(--mono); font-size:28px; font-weight:700; }
.page { display:none; }
.page.active { display:block; }
.page-hdr { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
.page-title { font-size:18px; font-weight:700; }
.chart-placeholder { height:200px; background:var(--s2); border-radius:8px; border:1px solid var(--b1); }
.tbl { width:100%; border-collapse:collapse; font-size:12px; }
.tbl th { text-align:left; padding:10px 12px; font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--t2); border-bottom:1px solid var(--b1); }
.tbl td { padding:10px 12px; border-bottom:1px solid var(--b1); }
.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; border:none; font-family:var(--sans); transition:all .12s; }
.btn-primary { background:var(--ac); color:#000; }
.btn-primary:hover { background:var(--ac2); }
```

---

## Spider QA — vista independiente en central

`resources/views/central/spider.blade.php`:

```blade
@extends('layouts.central')
@section('title', 'Spider QA')
@section('page-title', 'Spider QA')

@section('content')
  {{-- pegar aquí el HTML del spider-layout de superadmin.html --}}
  {{-- desde <div class="page-hdr"> hasta el cierre del spider-layout --}}
@endsection

@push('scripts')
  {{-- pegar aquí el <script> completo del spider --}}
@endpush
```

---

## Criterio de aceptación

- `localhost/central/login` → login → redirige a `localhost/central`
- `localhost/central` muestra dashboard con UI púrpura (IBM Plex, KPIs, MRR)
- `localhost/central/tenants` muestra tabla de tenants con sidebar de superadmin
- `localhost/central/billing` muestra billing con sidebar de superadmin
- `localhost/central/spider` muestra Spider QA completo como página propia
- `localhost/superadmin` redirige 301 a `localhost/central`
- El error SQL no regresa (las rutas siguen en el grupo de dominio central)
