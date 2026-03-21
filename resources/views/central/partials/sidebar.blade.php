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
  <a href="/central/planes" class="nav-item {{ request()->is('central/planes*') ? 'active' : '' }}">
    <span class="nav-icon">📦</span> Planes
  </a>
  <a href="/central/modulos" class="nav-item {{ request()->is('central/modulos*') ? 'active' : '' }}">
    <span class="nav-icon">🧩</span> Módulos
  </a>
  <a href="#" class="nav-item">
    <span class="nav-icon">🗂</span> Logs / Auditoría
  </a>
  <a href="/central/spider" class="nav-item {{ request()->is('central/spider*') ? 'active' : '' }}">
    <span class="nav-icon">🕸</span> Spider QA
  </a>
</div>
