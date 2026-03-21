# Fix: Prompt de contraseña al navegar Spider → Dashboard en central

## Síntoma

Al navegar de `/central/spider` a `/central` (dashboard), el navegador muestra el diálogo de "¿Guardar contraseña?" o "Reconectando", como si hubiera habido un logout y login automático.

## Causa raíz

Spider QA hace un login silencioso contra `/api/superadmin/login` (o `/api/central/login`) con email y password en texto plano para obtener un token Bearer. Esto ocurre en el JS del spider:

```javascript
// En el JS de spider — esto es el problema:
const r = await fetch(SU + '/api/superadmin/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: cfg('sa-email'), password: cfg('sa-pass') })
})
```

El navegador detecta que se envió un campo `password` en una petición fetch y activa el gestor de contraseñas, mostrando el prompt. Esto pasa especialmente en Chrome/Edge cuando:

1. El fetch incluye un body con campo `password`
2. La respuesta devuelve un token (el navegador lo interpreta como un login exitoso)
3. Al navegar a otra página, el navegador ofrece guardar/actualizar la contraseña

## Fix

### Opción A — Reutilizar la sesión Laravel existente (recomendada)

Spider no debe hacer su propio login. El usuario ya está autenticado en la sesión de Laravel cuando accede a `/central/spider`. Spider debe usar el token CSRF de la sesión actual para llamar a la API, o el backend debe proveer un endpoint que genere un token API a partir de la sesión activa:

```php
// Agregar en routes/web.php dentro del grupo central autenticado:
Route::post('/central/spider/token', function () {
    $token = auth('super_admin')->user()->createToken('spider-session')->plainTextToken;
    return response()->json(['token' => $token]);
})->middleware('auth:super_admin');
```

En el JS de spider, reemplazar el login silencioso por:

```javascript
// En lugar de hacer fetch a /api/superadmin/login con email+password:
async function getToken() {
    if (S.saT) return S.saT;
    const r = await fetch('/central/spider/token', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    });
    const d = await r.json();
    S.saT = d.token;
    return S.saT;
}
```

Esto nunca envía `password` en el body, así que el navegador no activa el gestor de contraseñas.

### Opción B — Renombrar el campo password en el fetch (parche rápido)

Si no se quiere tocar el backend, ofuscar el nombre del campo para que el navegador no lo reconozca como credencial:

```javascript
// Cambiar el campo de 'password' a algo que el navegador no reconozca:
body: JSON.stringify({ email: cfg('sa-email'), pwd: cfg('sa-pass') })
```

Y en el backend aceptar `pwd` en lugar de `password` para ese endpoint específico de spider/login.

> Esta opción es un parche. La opción A es la correcta.

### Opción C — Desactivar autocomplete en los inputs de spider

Los inputs de email y password en el sidebar de spider deben tener `autocomplete="off"` y `autocomplete="new-password"`:

```html
<input id="sa-email" value="admin@benderand.cl" autocomplete="off">
<input type="password" id="sa-pass" value="password" autocomplete="new-password">
```

> Esto ayuda pero no es suficiente por sí solo si el fetch sigue enviando el campo `password`.

## Fix completo recomendado (A + C juntos)

1. Agregar ruta `POST /central/spider/token` en el backend que devuelve token desde sesión activa
2. Reemplazar el login silencioso en el JS de spider por una llamada a ese endpoint
3. Agregar `autocomplete="new-password"` a los inputs de password en el sidebar de spider
4. Agregar `<meta name="csrf-token" content="{{ csrf_token() }}">` en el layout de central si no existe

## Criterio de aceptación

- Navegar `/central/spider` → ejecutar sync/spider → volver a `/central` no muestra ningún prompt de contraseña
- El navegador no detecta un evento de login/logout durante la sesión
- Spider sigue funcionando (obtiene token y puede llamar a la API)
