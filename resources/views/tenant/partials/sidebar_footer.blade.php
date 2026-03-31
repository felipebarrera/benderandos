<div class="p-3 border-t" style="border-color:#1e1e28;">
    <div class="flex items-center gap-3 mb-3">
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold" style="background:rgba(0,229,160,.15); color:var(--accent); border:1px solid rgba(0,229,160,.3);">
            {{ strtoupper(substr(auth()->user()->nombre ?? 'U', 0, 1)) }}
        </div>
        <div class="flex-1 min-w-0">
            <div class="text-xs font-semibold truncate">{{ auth()->user()->nombre ?? 'Usuario' }}</div>
            <div class="text-xs capitalize" style="color:#7878a0;">{{ $rol }}</div>
        </div>
    </div>
    <form action="{{ route('web.logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-ghost w-full" style="justify-content:flex-start; font-size:12px; color:var(--t2);">
            ⏻ Cerrar sesión
        </button>
    </form>
</div>
