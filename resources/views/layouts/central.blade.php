<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'SuperAdmin') - BenderAnd</title>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --bg:#08080a; --s1:#111115; --s2:#18181e; --s3:#1e1e28;
      --b1:#1e1e28; --b2:#2a2a3a; --b3:#38384a;
      --tx:#e8e8f0; --t2:#7878a0; --t3:#3a3a55;
      --ac:#e040fb; /* super admin = púrpura */
      --ac2:#b800d8;
      --ok:#00e5a0; --warn:#f5c518; --err:#ff3f5b; --info:#4488ff;
      --mono:'IBM Plex Mono',monospace;
      --sans:'IBM Plex Sans',sans-serif;
      --nav-w:220px;
      --top-h:52px;
    }
    html,body{height:100%;background:var(--bg);color:var(--tx);font-family:var(--sans)}
    body{display:flex;flex-direction:column;overflow:hidden}

    .topbar{
      height:var(--top-h); min-height:var(--top-h);
      background:var(--s1); border-bottom:1px solid var(--b1);
      display:flex; align-items:center; padding:0 20px; gap:12px;
      flex-shrink:0; z-index:20;
    }
    .tb-brand{font-family:var(--mono);font-weight:700;font-size:17px;letter-spacing:-1px}
    .tb-sep{width:1px;height:18px;background:var(--b2)}
    .tb-title{font-size:12px;font-weight:600;color:var(--t2);flex:1}
    .tb-badge{
      font-family:var(--mono);font-size:9px;font-weight:700;letter-spacing:1px;
      text-transform:uppercase;color:var(--ac);
      border:1px solid rgba(224,64,251,.3);padding:3px 10px;
    }
    .tb-avatar{
      width:32px;height:32px;border-radius:50%;
      background:rgba(224,64,251,.15);border:1px solid rgba(224,64,251,.3);
      display:flex;align-items:center;justify-content:center;
      font-family:var(--mono);font-size:11px;font-weight:700;color:var(--ac);
      cursor:pointer;flex-shrink:0;
    }

    .layout{flex:1;display:flex;overflow:hidden;min-height:0}

    .sidebar{
      width:var(--nav-w);min-width:var(--nav-w);
      background:var(--s1);border-right:1px solid var(--b1);
      display:flex;flex-direction:column;overflow-y:auto;overflow-x:hidden;
      flex-shrink:0;
    }
    .sidebar::-webkit-scrollbar{width:2px}
    .sidebar::-webkit-scrollbar-thumb{background:var(--b2)}

    .nav-section{padding:16px 12px 4px}
    .nav-section-lbl{
      font-family:var(--mono);font-size:9px;font-weight:700;
      letter-spacing:1.5px;text-transform:uppercase;color:var(--t3);
      padding:0 8px;margin-bottom:4px;
    }
    .nav-item{
      display:flex;align-items:center;gap:10px;
      padding:9px 12px;border-radius:8px;
      font-size:13px;font-weight:500;color:var(--t2);
      cursor:pointer;transition:all .12s;text-decoration:none;
      -webkit-tap-highlight-color:transparent;
      position:relative;
    }
    .nav-item:hover{background:var(--s2);color:var(--tx)}
    .nav-item.active{background:rgba(224,64,251,.1);color:var(--ac)}
    .nav-item.active::before{
      content:'';position:absolute;left:0;top:6px;bottom:6px;
      width:3px;background:var(--ac);border-radius:0 2px 2px 0;
    }
    .nav-icon{font-size:14px;width:18px;text-align:center;flex-shrink:0}

    .sidebar-footer{
      margin-top:auto;padding:12px;border-top:1px solid var(--b1);
    }
    .logout-btn{
      width:100%;padding:9px 12px;
      background:none;border:1px solid var(--b2);border-radius:8px;
      color:var(--t2);font-family:var(--sans);font-size:12px;
      cursor:pointer;transition:all .12s;text-align:left;
      display:flex;align-items:center;gap:8px;
    }
    .logout-btn:hover{border-color:var(--err);color:var(--err)}

    .content{flex:1;overflow-y:auto;min-width:0;padding:24px}
    .content::-webkit-scrollbar{width:4px}
    .content::-webkit-scrollbar-thumb{background:var(--b2)}

    .card{
      background:var(--s1);border:1px solid var(--b1);
      border-radius:12px;padding:24px;margin-bottom:24px;
    }
    .card-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
    .card-title{font-size:14px;font-weight:600;color:var(--tx)}

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

    .badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 11px;
      background: var(--s2);
      color: var(--tx);
    }
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
      @include('central.partials.sidebar')
      <div class="sidebar-footer">
        <form action="{{ route('central.logout') }}" method="POST">
          @csrf
          <button type="submit" class="logout-btn">⏻ Cerrar sesión</button>
        </form>
      </div>
    </aside>
    <main class="content">
      @yield('content')
    </main>
  </div>

  @stack('scripts')
</body>
</html>
