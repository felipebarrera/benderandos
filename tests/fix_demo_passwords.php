<?php
$slugs = ['demo-legal','demo-padel','demo-motel','demo-abarrotes','demo-ferreteria','demo-medico','demo-saas'];
foreach ($slugs as $slug) {
    $tenant = App\Models\Central\Tenant::find($slug);
    if (!$tenant) { echo "No existe: {$slug}\n"; continue; }
    tenancy()->initialize($tenant);
    $user = App\Models\Tenant\Usuario::where('rol', 'admin')->first();
    if (!$user) { echo "Sin admin: {$slug}\n"; tenancy()->end(); continue; }
    $hashOk = $user->clave_hash && str_starts_with($user->clave_hash, '$2y$');
    echo "{$slug} -> {$user->email} hash_ok=" . ($hashOk ? 'YES' : 'NO') . "\n";

    // Fix: reset password with clave_hash
    $user->clave_hash = bcrypt('demo1234');
    $user->save();
    echo "  -> Password reset OK\n";

    tenancy()->end();
}
