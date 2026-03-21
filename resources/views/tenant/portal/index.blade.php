{{-- resources/views/tenant/portal/index.blade.php --}}
{{-- VARIABLES ESPERADAS:
  $config  → RubroConfig (portal_color_primario, portal_descripcion, portal_horario,
                          portal_telefono, portal_direccion, portal_whatsapp_numero,
                          portal_telegram_url, portal_logo_url, industria_preset)
  $tenant  → tenant() — nombre del negocio
  $productos → Collection<Producto> (nombre, valor_venta, descripcion, imagen_url, tipo_producto)
--}}
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $tenant->nombre ?? 'Bienvenido' }}</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
:root {
  --ac: {{ $config->portal_color_primario ?? '#00e5a0' }};
  --ac-dim: color-mix(in srgb, var(--ac) 15%, transparent);
  --ac-mid: color-mix(in srgb, var(--ac) 30%, transparent);
  --bg: #f7f6f3;
  --s1: #ffffff;
  --tx: #1a1a18;
  --t2: #6b6b60;
  --t3: #b0b0a0;
  --r: 14px;
  --display: 'Syne', sans-serif;
  --body: 'DM Sans', sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--tx);font-family:var(--body);min-height:100vh;font-size:15px;line-height:1.6}

/* NAV */
.nav{position:sticky;top:0;z-index:100;background:rgba(247,246,243,.92);backdrop-filter:blur(16px);border-bottom:1px solid rgba(0,0,0,.07);padding:0 20px;display:flex;align-items:center;justify-content:space-between;height:60px;gap:16px}
.nav-brand{font-family:var(--display);font-size:17px;font-weight:800;letter-spacing:-.5px;flex-shrink:0;display:flex;align-items:center;gap:8px}
.nav-logo{width:32px;height:32px;border-radius:8px;background:var(--ac);display:flex;align-items:center;justify-content:center;font-size:14px;color:#000;font-weight:800;flex-shrink:0}
.nav-links{display:flex;align-items:center;gap:24px}
.nav-links a{font-size:13px;font-weight:500;color:var(--t2);text-decoration:none;transition:color .15s}
.nav-links a:hover{color:var(--tx)}
.nav-right{display:flex;align-items:center;gap:10px;flex-shrink:0}
.cart-btn{position:relative;background:var(--tx);color:#fff;border:none;border-radius:var(--r);padding:8px 14px;font-family:var(--body);font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .15s}
.cart-btn:hover{background:var(--ac);color:#000}
.cart-badge{position:absolute;top:-6px;right:-6px;background:var(--ac);color:#000;border-radius:50%;width:18px;height:18px;font-size:10px;font-weight:800;display:flex;align-items:center;justify-content:center;font-family:var(--display)}
.staff-link{font-size:11px;color:var(--t3);text-decoration:none;font-weight:500;transition:color .15s}
.staff-link:hover{color:var(--t2)}

/* HERO */
.hero{padding:56px 20px 48px;max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center}
.hero-text{}
.hero-tag{display:inline-flex;align-items:center;gap:6px;background:var(--ac-dim);border:1px solid var(--ac-mid);border-radius:20px;padding:4px 12px;font-size:11px;font-weight:700;color:var(--tx);letter-spacing:.5px;text-transform:uppercase;margin-bottom:16px}
.hero-title{font-family:var(--display);font-size:clamp(32px,4vw,52px);font-weight:800;line-height:1.1;letter-spacing:-1.5px;margin-bottom:16px}
.hero-title span{color:var(--ac)}
.hero-sub{font-size:15px;color:var(--t2);line-height:1.7;max-width:420px;margin-bottom:28px}
.hero-ctas{display:flex;gap:10px;flex-wrap:wrap}
.btn-wa{display:inline-flex;align-items:center;gap:7px;background:#25d366;color:#fff;border:none;border-radius:var(--r);padding:12px 20px;font-family:var(--body);font-size:13px;font-weight:700;text-decoration:none;transition:all .15s;cursor:pointer}
.btn-wa:hover{background:#1db954;transform:translateY(-1px)}
.btn-tg{display:inline-flex;align-items:center;gap:7px;background:#2AABEE;color:#fff;border:none;border-radius:var(--r);padding:12px 20px;font-family:var(--body);font-size:13px;font-weight:700;text-decoration:none;transition:all .15s;cursor:pointer}
.btn-tg:hover{background:#229ed9;transform:translateY(-1px)}
.hero-info{display:flex;flex-direction:column;gap:8px;margin-top:24px}
.info-row{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--t2)}
.info-icon{width:20px;height:20px;background:var(--ac-dim);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0}

/* HERO VISUAL */
.hero-visual{display:flex;flex-direction:column;gap:8px}
.hv-card{background:var(--s1);border-radius:var(--r);padding:14px 16px;border:1px solid rgba(0,0,0,.06);display:flex;align-items:center;gap:12px;animation:floatUp .4s both}
.hv-card:nth-child(2){animation-delay:.1s;margin-left:16px}
.hv-card:nth-child(3){animation-delay:.2s}
@keyframes floatUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.hv-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.hv-label{font-size:12px;font-weight:700;font-family:var(--display)}
.hv-sub{font-size:11px;color:var(--t2);margin-top:1px}
.hv-val{font-size:14px;font-weight:700;color:var(--ac);margin-left:auto;font-family:var(--display)}

/* SECTION */
.section{max-width:1100px;margin:0 auto;padding:0 20px 60px}
.section-head{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:24px}
.section-title{font-family:var(--display);font-size:22px;font-weight:800;letter-spacing:-.5px}
.section-sub{font-size:12px;color:var(--t2);margin-top:4px}
.section-link{font-size:12px;font-weight:600;color:var(--ac);text-decoration:none}
.section-link:hover{text-decoration:underline}

/* DIVIDER */
.divider{height:1px;background:rgba(0,0,0,.07);margin:0 20px 40px}

/* PRODUCT GRID */
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.pcard{background:var(--s1);border-radius:var(--r);overflow:hidden;border:1px solid rgba(0,0,0,.06);display:flex;flex-direction:column;transition:all .2s;cursor:pointer}
.pcard:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(0,0,0,.1);border-color:var(--ac)}
.pcard-img{aspect-ratio:1;background:linear-gradient(135deg,#f0efe9,#e8e7e0);display:flex;align-items:center;justify-content:center;font-size:36px;position:relative;overflow:hidden}
.pcard-img img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0}
.pcard-badge{position:absolute;top:8px;left:8px;background:var(--ac);color:#000;border-radius:6px;font-size:9px;font-weight:800;padding:3px 7px;font-family:var(--display);letter-spacing:.5px}
.pcard-body{padding:12px;flex:1;display:flex;flex-direction:column}
.pcard-cat{font-size:10px;color:var(--t3);font-weight:600;letter-spacing:.5px;text-transform:uppercase;margin-bottom:4px}
.pcard-name{font-size:13px;font-weight:700;font-family:var(--display);line-height:1.3;margin-bottom:6px;letter-spacing:-.2px}
.pcard-desc{font-size:11px;color:var(--t2);line-height:1.5;flex:1;margin-bottom:10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.pcard-foot{display:flex;align-items:center;justify-content:space-between;gap:6px}
.pcard-price{font-family:var(--display);font-size:16px;font-weight:800;color:var(--tx)}
.pcard-price-unit{font-size:10px;color:var(--t2);font-weight:400}
.add-btn{background:var(--tx);color:#fff;border:none;border-radius:9px;width:30px;height:30px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;flex-shrink:0;font-size:16px}
.add-btn:hover{background:var(--ac);color:#000}
.add-btn.added{background:var(--ac);color:#000}

/* CARRITO DRAWER */
.cart-overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:200;opacity:0;pointer-events:none;transition:opacity .25s;backdrop-filter:blur(4px)}
.cart-overlay.open{opacity:1;pointer-events:all}
.cart-drawer{position:fixed;top:0;right:0;bottom:0;width:min(400px,100vw);background:var(--s1);z-index:201;transform:translateX(100%);transition:transform .3s cubic-bezier(.32,.72,0,1);display:flex;flex-direction:column;box-shadow:-20px 0 60px rgba(0,0,0,.15)}
.cart-drawer.open{transform:translateX(0)}
.cart-head{padding:20px;border-bottom:1px solid rgba(0,0,0,.07);display:flex;align-items:center;justify-content:space-between}
.cart-head-title{font-family:var(--display);font-size:18px;font-weight:800;letter-spacing:-.3px}
.cart-close{background:none;border:none;font-size:20px;cursor:pointer;color:var(--t2);line-height:1;padding:4px;border-radius:8px;transition:all .15s}
.cart-close:hover{background:rgba(0,0,0,.06);color:var(--tx)}
.cart-body{flex:1;overflow-y:auto;padding:16px}
.cart-empty{text-align:center;padding:48px 20px;color:var(--t2)}
.cart-empty-icon{font-size:48px;margin-bottom:12px}
.cart-empty-text{font-size:14px;font-weight:500}
.cart-item{display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg);border-radius:var(--r);margin-bottom:8px}
.ci-emoji{font-size:24px;width:44px;height:44px;background:var(--s1);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ci-info{flex:1;min-width:0}
.ci-name{font-size:13px;font-weight:700;font-family:var(--display);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ci-price{font-size:11px;color:var(--t2);margin-top:2px}
.ci-controls{display:flex;align-items:center;gap:6px;flex-shrink:0}
.ci-btn{width:24px;height:24px;border:none;border-radius:7px;background:var(--s1);cursor:pointer;font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:center;transition:all .15s}
.ci-btn:hover{background:var(--ac);color:#000}
.ci-qty{font-family:var(--display);font-size:13px;font-weight:700;min-width:20px;text-align:center}
.cart-foot{padding:16px;border-top:1px solid rgba(0,0,0,.07)}
.cart-total{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:14px;padding:0 4px}
.cart-total-lbl{font-size:13px;color:var(--t2)}
.cart-total-val{font-family:var(--display);font-size:22px;font-weight:800}
.checkout-wa{width:100%;background:#25d366;color:#fff;border:none;border-radius:var(--r);padding:14px;font-family:var(--body);font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .15s;margin-bottom:8px}
.checkout-wa:hover{background:#1db954;transform:translateY(-1px)}
.checkout-tg{width:100%;background:#2AABEE;color:#fff;border:none;border-radius:var(--r);padding:12px;font-family:var(--body);font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .15s}
.checkout-tg:hover{background:#229ed9}
.checkout-note{text-align:center;font-size:11px;color:var(--t3);margin-top:8px}

/* CONTACT STRIP */
.contact-strip{background:var(--tx);color:#fff;padding:40px 20px;margin-top:40px}
.contact-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:center}
.contact-title{font-family:var(--display);font-size:24px;font-weight:800;margin-bottom:8px;letter-spacing:-.5px}
.contact-sub{font-size:13px;opacity:.65;line-height:1.6}
.contact-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap}

/* FOOTER */
.footer{background:var(--s1);border-top:1px solid rgba(0,0,0,.07);padding:28px 20px;text-align:center}
.footer-brand{font-family:var(--display);font-size:16px;font-weight:800;margin-bottom:6px;letter-spacing:-.3px}
.footer-staff{font-size:11px;color:var(--t3);margin-top:12px}
.footer-staff a{color:var(--t3);text-decoration:none;transition:color .15s;font-weight:500}
.footer-staff a:hover{color:var(--t2)}
.footer-copy{font-size:11px;color:var(--t3);margin-top:8px}

/* TOAST */
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(60px);background:var(--tx);color:#fff;border-radius:var(--r);padding:10px 18px;font-size:13px;font-weight:600;z-index:300;transition:transform .25s;pointer-events:none;display:flex;align-items:center;gap:7px;white-space:nowrap}
.toast.show{transform:translateX(-50%) translateY(0)}

/* RESPONSIVE */
@media(max-width:680px){
  .hero{grid-template-columns:1fr;gap:28px}
  .hero-visual{display:none}
  .contact-inner{grid-template-columns:1fr}
  .contact-actions{justify-content:flex-start}
  .nav-links{display:none}
  .product-grid{grid-template-columns:repeat(2,1fr)}
}
</style>
</head>
<body>

<!-- NAV -->
<header class="nav">
  <div class="nav-brand">
    @if($config->portal_logo_url)
      <img src="{{ $config->portal_logo_url }}" alt="Logo" class="nav-logo" style="object-fit:cover">
    @else
      <div class="nav-logo">{{ strtoupper(substr($tenant->nombre ?? 'B', 0, 1)) }}</div>
    @endif
    {{ $tenant->nombre ?? 'Mi Negocio' }}
  </div>

  <nav class="nav-links">
    <a href="#inicio">Inicio</a>
    <a href="#catalogo">Catálogo</a>
    <a href="#contacto">Contacto</a>
  </nav>

  <div class="nav-right">
    <button class="cart-btn" onclick="abrirCarrito()" id="cartBtn" style="display:none">
      🛒 <span id="cartLabel">Mi pedido</span>
      <span class="cart-badge" id="cartCount">0</span>
    </button>
    {{-- FIX: /auth/login/web en vez de /login --}}
    <a href="/auth/login/web" class="staff-link">Acceso staff</a>
  </div>
</header>

<!-- HERO -->
<section class="hero" id="inicio">
  <div class="hero-text">
    @if($config->industria_preset)
      <div class="hero-tag">
        @switch($config->industria_preset)
          @case('abarrotes') 🛒 Almacén @break
          @case('ferreteria') 🔧 Ferretería @break
          @case('medico') 🏥 Médico @break
          @case('legal') ⚖️ Estudio Jurídico @break
          @case('padel') 🎾 Canchas @break
          @case('motel') 🏨 Hospedaje @break
          @default ✨ Bienvenido @endswitch
      </div>
    @endif

    <h1 class="hero-title">
      {{ $tenant->nombre ?? 'Mi Negocio' }}<br>
      <span>a tu servicio</span>
    </h1>

    @if($config->portal_descripcion)
      <p class="hero-sub">{{ $config->portal_descripcion }}</p>
    @else
      <p class="hero-sub">Haz tu pedido directo por WhatsApp o Telegram. Respuesta rápida, sin vueltas.</p>
    @endif

    <div class="hero-ctas">
      @if($config->portal_whatsapp_numero)
        <a href="https://wa.me/{{ preg_replace('/\D/','',$config->portal_whatsapp_numero) }}?text={{ urlencode('Hola, quisiera hacer un pedido') }}"
           target="_blank" class="btn-wa">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          Pedir por WhatsApp
        </a>
      @endif
      @if($config->portal_telegram_url)
        <a href="{{ $config->portal_telegram_url }}" target="_blank" class="btn-tg">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 1 0 24 12 12 12 0 0 0 11.944 0zm4.986 8.058l-2.036 9.588c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L7.46 13.93l-2.923-.913c-.634-.197-.647-.634.136-.938l11.39-4.39c.527-.197.99.12.876.369z"/></svg>
          Chatear por Telegram
        </a>
      @endif
    </div>

    @if($config->portal_horario || $config->portal_telefono || $config->portal_direccion)
      <div class="hero-info">
        @if($config->portal_horario)
          <div class="info-row">
            <div class="info-icon">🕐</div>
            {{ $config->portal_horario }}
          </div>
        @endif
        @if($config->portal_telefono)
          <div class="info-row">
            <div class="info-icon">📞</div>
            {{ $config->portal_telefono }}
          </div>
        @endif
        @if($config->portal_direccion)
          <div class="info-row">
            <div class="info-icon">📍</div>
            {{ $config->portal_direccion }}
          </div>
        @endif
      </div>
    @endif
  </div>

  <!-- Hero visual — tarjetas animadas -->
  <div class="hero-visual">
    <div class="hv-card">
      <div class="hv-icon" style="background:color-mix(in srgb,var(--ac) 15%,transparent)">🛍️</div>
      <div>
        <div class="hv-label">Pedidos rápidos</div>
        <div class="hv-sub">Arma tu carrito y envía por WA</div>
      </div>
      <div class="hv-val">→</div>
    </div>
    <div class="hv-card">
      <div class="hv-icon" style="background:rgba(37,211,102,.12)">📦</div>
      <div>
        <div class="hv-label">{{ $productos->count() }} productos</div>
        <div class="hv-sub">Disponibles ahora</div>
      </div>
      <div class="hv-val" style="color:#25d366">●</div>
    </div>
    <div class="hv-card">
      <div class="hv-icon" style="background:rgba(42,171,238,.12)">⚡</div>
      <div>
        <div class="hv-label">Respuesta inmediata</div>
        <div class="hv-sub">Bot disponible 24/7</div>
      </div>
      <div class="hv-val" style="color:#2AABEE">→</div>
    </div>
  </div>
</section>

<!-- PRODUCTOS -->
@if($productos->count() > 0)
<div class="divider"></div>
<section class="section" id="catalogo">
  <div class="section-head">
    <div>
      <div class="section-title">Catálogo</div>
      <div class="section-sub">{{ $productos->count() }} productos disponibles · Agrega al carrito y envía tu pedido</div>
    </div>
    <a href="/catalogo" class="section-link">Ver todo →</a>
  </div>
  <div class="product-grid" id="productGrid">
    @foreach($productos as $p)
      @php
        $emoji = match($p->tipo_producto ?? '') {
          'servicio'    => '🛠️',
          'renta'       => '⏱️',
          'fraccionado' => '⚖️',
          default       => '📦',
        };
        // Formatear precio chileno
        $precio = '$' . number_format($p->valor_venta / 100, 0, ',', '.');
        $unidad = $p->tipo_producto === 'fraccionado' ? ('/'.($p->unidad_medida ?? 'kg')) : '';
      @endphp
      <div class="pcard" data-id="{{ $p->id }}" data-nombre="{{ addslashes($p->nombre) }}" data-precio="{{ $p->valor_venta }}">
        <div class="pcard-img">
          @if($p->imagen_url ?? null)
            <img src="{{ $p->imagen_url }}" alt="{{ $p->nombre }}" loading="lazy">
          @else
            {{ $emoji }}
          @endif
          @if($p->tipo_producto === 'servicio')
            <span class="pcard-badge">SERVICIO</span>
          @elseif($p->tipo_producto === 'fraccionado')
            <span class="pcard-badge">POR {{ strtoupper($p->unidad_medida ?? 'kg') }}</span>
          @endif
        </div>
        <div class="pcard-body">
          <div class="pcard-cat">{{ $p->categoria ?? 'General' }}</div>
          <div class="pcard-name">{{ $p->nombre }}</div>
          @if($p->descripcion ?? null)
            <div class="pcard-desc">{{ $p->descripcion }}</div>
          @endif
          <div class="pcard-foot">
            <div>
              <div class="pcard-price">{{ $precio }}<span class="pcard-price-unit">{{ $unidad }}</span></div>
            </div>
            <button class="add-btn" onclick="agregarAlCarrito({{ $p->id }}, '{{ addslashes($p->nombre) }}', {{ $p->valor_venta }}, '{{ $emoji }}')" title="Agregar al pedido">
              +
            </button>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</section>
@endif

<!-- CONTACTO -->
<div class="contact-strip" id="contacto">
  <div class="contact-inner">
    <div>
      <div class="contact-title">¿Hablamos?</div>
      <div class="contact-sub">El bot responde al instante. Consultas, pedidos, información de productos — todo por WhatsApp o Telegram.</div>
    </div>
    <div class="contact-actions">
      @if($config->portal_whatsapp_numero)
        <a href="https://wa.me/{{ preg_replace('/\D/','',$config->portal_whatsapp_numero) }}?text={{ urlencode('Hola, quisiera información') }}"
           target="_blank" class="btn-wa">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          WhatsApp
        </a>
      @endif
      @if($config->portal_telegram_url)
        <a href="{{ $config->portal_telegram_url }}" target="_blank" class="btn-tg">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 1 0 24 12 12 12 0 0 0 11.944 0zm4.986 8.058l-2.036 9.588c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L7.46 13.93l-2.923-.913c-.634-.197-.647-.634.136-.938l11.39-4.39c.527-.197.99.12.876.369z"/></svg>
          Telegram
        </a>
      @endif
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-brand">{{ $tenant->nombre ?? 'Mi Negocio' }}</div>
  <div style="font-size:12px;color:var(--t2)">Tu solución de confianza · Potenciado por BenderAnd</div>
  <div class="footer-staff">
    <a href="/auth/login/web">Acceso staff →</a>
  </div>
  <div class="footer-copy">© {{ date('Y') }} {{ $tenant->nombre ?? '' }} · Todos los derechos reservados</div>
</footer>

<!-- DRAWER DEL CARRITO -->
<div class="cart-overlay" id="cartOverlay" onclick="cerrarCarrito()"></div>
<div class="cart-drawer" id="cartDrawer">
  <div class="cart-head">
    <div class="cart-head-title">🛒 Mi pedido</div>
    <button class="cart-close" onclick="cerrarCarrito()">✕</button>
  </div>
  <div class="cart-body" id="cartBody">
    <div class="cart-empty">
      <div class="cart-empty-icon">🛍️</div>
      <div class="cart-empty-text">Aún no agregaste nada</div>
      <div style="font-size:11px;color:var(--t3);margin-top:4px">Elige productos del catálogo</div>
    </div>
  </div>
  <div class="cart-foot" id="cartFoot" style="display:none">
    <div class="cart-total">
      <span class="cart-total-lbl">Total estimado</span>
      <span class="cart-total-val" id="cartTotal">$0</span>
    </div>
    <button class="checkout-wa" onclick="checkoutWA()">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
      Enviar pedido por WhatsApp
    </button>
    @if($config->portal_telegram_url ?? null)
      <button class="checkout-tg" onclick="checkoutTG()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 1 0 24 12 12 12 0 0 0 11.944 0zm4.986 8.058l-2.036 9.588c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L7.46 13.93l-2.923-.913c-.634-.197-.647-.634.136-.938l11.39-4.39c.527-.197.99.12.876.369z"/></svg>
        Enviar por Telegram
      </button>
    @endif
    <div class="checkout-note">El bot recibirá tu pedido y confirmará disponibilidad</div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast">✅ Agregado al pedido</div>

<script>
// ─── ESTADO DEL CARRITO ───────────────────────────────────────
let carrito = JSON.parse(localStorage.getItem('carrito_{{ $tenant->id ?? "demo" }}') ?? '[]');
const WA_NUM  = '{{ preg_replace("/\D/", "", $config->portal_whatsapp_numero ?? "") }}';
const TG_URL  = '{{ $config->portal_telegram_url ?? "" }}';
const NOMBRE_NEGOCIO = '{{ addslashes($tenant->nombre ?? "el negocio") }}';

function guardarCarrito() {
  localStorage.setItem('carrito_{{ $tenant->id ?? "demo" }}', JSON.stringify(carrito));
}

// ─── AGREGAR AL CARRITO ───────────────────────────────────────
function agregarAlCarrito(id, nombre, precio, emoji) {
  const idx = carrito.findIndex(i => i.id === id);
  if (idx >= 0) {
    carrito[idx].qty++;
  } else {
    carrito.push({ id, nombre, precio, emoji, qty: 1 });
  }
  guardarCarrito();
  actualizarUI();
  mostrarToast('✅ ' + nombre + ' agregado');

  // Marcar botón visualmente
  const btn = document.querySelector(`[data-id="${id}"] .add-btn`);
  if (btn) { btn.classList.add('added'); btn.textContent = '✓'; }
}

// ─── CAMBIAR CANTIDAD ─────────────────────────────────────────
function cambiarQty(id, delta) {
  const idx = carrito.findIndex(i => i.id === id);
  if (idx < 0) return;
  carrito[idx].qty += delta;
  if (carrito[idx].qty <= 0) {
    carrito.splice(idx, 1);
    // Restaurar botón
    const btn = document.querySelector(`[data-id="${id}"] .add-btn`);
    if (btn) { btn.classList.remove('added'); btn.textContent = '+'; }
  }
  guardarCarrito();
  actualizarUI();
  renderCarrito();
}

// ─── UI ───────────────────────────────────────────────────────
function actualizarUI() {
  const total = carrito.reduce((s, i) => s + i.qty, 0);
  const cartBtn = document.getElementById('cartBtn');
  const cartCount = document.getElementById('cartCount');
  if (total > 0) {
    cartBtn.style.display = 'flex';
    cartCount.textContent = total;
  } else {
    cartBtn.style.display = 'none';
  }
}

function formatCLP(n) {
  return '$' + Math.round(n / 100).toLocaleString('es-CL');
}

function renderCarrito() {
  const body = document.getElementById('cartBody');
  const foot = document.getElementById('cartFoot');
  const totalEl = document.getElementById('cartTotal');

  if (carrito.length === 0) {
    body.innerHTML = `<div class="cart-empty">
      <div class="cart-empty-icon">🛍️</div>
      <div class="cart-empty-text">Aún no agregaste nada</div>
      <div style="font-size:11px;color:var(--t3);margin-top:4px">Elige productos del catálogo</div>
    </div>`;
    foot.style.display = 'none';
    return;
  }

  const totalPrecio = carrito.reduce((s, i) => s + i.precio * i.qty, 0);
  totalEl.textContent = formatCLP(totalPrecio);
  foot.style.display = 'block';

  body.innerHTML = carrito.map(i => `
    <div class="cart-item">
      <div class="ci-emoji">${i.emoji}</div>
      <div class="ci-info">
        <div class="ci-name">${i.nombre}</div>
        <div class="ci-price">${formatCLP(i.precio)} c/u · Total: ${formatCLP(i.precio * i.qty)}</div>
      </div>
      <div class="ci-controls">
        <button class="ci-btn" onclick="cambiarQty(${i.id}, -1)">−</button>
        <span class="ci-qty">${i.qty}</span>
        <button class="ci-btn" onclick="cambiarQty(${i.id}, +1)">+</button>
      </div>
    </div>
  `).join('');
}

// ─── DRAWER ───────────────────────────────────────────────────
function abrirCarrito() {
  renderCarrito();
  document.getElementById('cartOverlay').classList.add('open');
  document.getElementById('cartDrawer').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function cerrarCarrito() {
  document.getElementById('cartOverlay').classList.remove('open');
  document.getElementById('cartDrawer').classList.remove('open');
  document.body.style.overflow = '';
}

// ─── CHECKOUT ─────────────────────────────────────────────────
function buildMensaje() {
  let msg = `Hola ${NOMBRE_NEGOCIO}, quiero hacer el siguiente pedido:\n\n`;
  carrito.forEach(i => {
    msg += `• ${i.nombre} × ${i.qty} = ${formatCLP(i.precio * i.qty)}\n`;
  });
  const total = carrito.reduce((s, i) => s + i.precio * i.qty, 0);
  msg += `\n*Total estimado: ${formatCLP(total)}*`;
  return encodeURIComponent(msg);
}

function checkoutWA() {
  if (!WA_NUM) { mostrarToast('⚠️ WhatsApp no configurado'); return; }
  window.open(`https://wa.me/${WA_NUM}?text=${buildMensaje()}`, '_blank');
}

function checkoutTG() {
  if (!TG_URL) { mostrarToast('⚠️ Telegram no configurado'); return; }
  window.open(TG_URL, '_blank');
}

// ─── TOAST ────────────────────────────────────────────────────
let toastTimer;
function mostrarToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 2200);
}

// ─── INIT ─────────────────────────────────────────────────────
actualizarUI();

// Restaurar estado visual de botones
carrito.forEach(i => {
  const btn = document.querySelector(`[data-id="${i.id}"] .add-btn`);
  if (btn) { btn.classList.add('added'); btn.textContent = '✓'; }
});

// Cerrar drawer con Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarCarrito(); });
</script>

</body>
</html>
