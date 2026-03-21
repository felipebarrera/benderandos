# Fix: Credenciales y URLs tenants demo

## Problemas

1. URLs sin puerto 8000
2. Contraseñas no funcionan — el seeder probablemente no corrió correctamente o las contraseñas no se hashearon bien

## Fix en DemoTenantsSeeder / TenantDemoDataSeeder

### URLs con puerto — agregar en domains

Los dominios deben registrarse sin puerto (Laravel Tenancy maneja el puerto aparte), pero al acceder en desarrollo siempre usar `:8000`:

```
http://demo-legal.localhost:8000
http://demo-padel.localhost:8000
http://demo-motel.localhost:8000
http://demo-abarrotes.localhost:8000
http://demo-ferreteria.localhost:8000
http://demo-medico.localhost:8000
http://demo-saas.localhost:8000
```

### Contraseñas — verificar hash

El problema puede ser que `Hash::make` no se está usando correctamente o que la tabla usa un campo distinto. Verificar dentro del tenant:

```bash
docker exec benderandos_app php artisan tinker
```

```php
// Inicializar tenant demo-legal:
$tenant = App\Models\Tenant::find('demo-legal');
tenancy()->initialize($tenant);

// Ver si existe el usuario:
App\Models\Usuario::where('email', 'admin@demo-legal.cl')->first();

// Si existe pero la contraseña no funciona, resetearla:
$user = App\Models\Usuario::where('email', 'admin@demo-legal.cl')->first();
$user->password = bcrypt('demo1234');
$user->save();
echo 'OK';
```

### Reset masivo de contraseñas para todos los tenants demo

```bash
docker exec benderandos_app php artisan tinker --execute="
\$slugs = ['demo-legal','demo-padel','demo-motel','demo-abarrotes','demo-ferreteria','demo-medico','demo-saas'];
foreach (\$slugs as \$slug) {
    \$tenant = App\Models\Tenant::find(\$slug);
    if (!\$tenant) { echo 'No existe: '.\$slug.PHP_EOL; continue; }
    tenancy()->initialize(\$tenant);
    \$user = App\Models\Usuario::where('rol', 'admin')->first();
    if (!\$user) { echo 'Sin admin: '.\$slug.PHP_EOL; tenancy()->end(); continue; }
    \$user->password = bcrypt('demo1234');
    \$user->save();
    echo 'OK: '.\$slug.' -> '.\$user->email.PHP_EOL;
    tenancy()->end();
}
"
```

## Tabla de acceso final

| Tenant | URL login | Email | Password |
|---|---|---|---|
| Legal | `http://demo-legal.localhost:8000/auth/login` | `admin@demo-legal.cl` | `demo1234` |
| Pádel | `http://demo-padel.localhost:8000/auth/login` | `admin@demo-padel.cl` | `demo1234` |
| Motel | `http://demo-motel.localhost:8000/auth/login` | `admin@demo-motel.cl` | `demo1234` |
| Abarrotes | `http://demo-abarrotes.localhost:8000/auth/login` | `admin@demo-abarrotes.cl` | `demo1234` |
| Ferretería | `http://demo-ferreteria.localhost:8000/auth/login` | `admin@demo-ferreteria.cl` | `demo1234` |
| Médico | `http://demo-medico.localhost:8000/auth/login` | `admin@demo-medico.cl` | `demo1234` |
| SaaS | `http://demo-saas.localhost:8000/auth/login` | `admin@demo-saas.cl` | `demo1234` |

## Si el login es en /central/login (web session)

La URL de login web es diferente a la API. Para acceso por navegador usar:

```
http://demo-legal.localhost:8000/auth/login/web
```

o simplemente:

```
http://demo-legal.localhost:8000/login
```

Ver qué ruta responde con 200 para este tenant.
