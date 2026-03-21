# Walkthrough: Hito 15 (Marketing QR y Fidelización)

Se ha completado satisfactoriamente el Hito 15, implementando un motor interno de generación de códigos QR para campañas promocionales estructuradas que cruzan hasta el punto de venta (POS).

## Cambios Implementados

### 1. Base de Datos
- `campanas_marketing`: Configura reglas de campaña (tipo de acción, stock de usos, cupones `%` o `$`, 2x1, apertura de WA). 
- `qr_campanas`: Almacena instanciaciones únicas de QRs (ej. ubicaciones físicas "Mesa 1", "Flyer Centro").
- `escaneos_qr`: Log de tracking (IP, device) registrando cuantas personas leyeron los QR y la conversión a `$`.

### 2. Generación de QRs via `MarketingService`
En lugar de depender de pesadas librerías de PHP instaladas localmente (que a menudo dan problemas con GD/Imagick), el sistema se integró con la API **QuickChart.io** que genera los QRs en `PNG` ultra veloces. 

### 3. Portal Público de Captación (`QrLandingController`)
Cuando un usuario escanea con su cámara, visita silenciosamente la ruta `/qr/{uuid}`.
1. Se captura la huella (dispositivo tipo Mobile o Desktop, e IP).
2. Se evalúa el estado de la campaña.
   - **Expirada/Agotada**: Muestra vista `qr_expirado.blade.php`.
   - **Abrir WhatsApp / Encuestas**: Redirige directamente al link objetivo.
   - **Cupón de descuento**: Muestra `qr_cupon.blade.php` con el código POS para presentar al cajero.

### 4. Admin UI (Dashboard Marketing)
- Link agregado al sidebar: `Marketing QR`.
- **Métricas Reales**: Campañas activas, escaneos totales, conversiones POS.
- **Grillas**: Listado de campañas con botón para generar y descargar QRs.

### 5. Integración con Punto de Venta (POS)
El controlador `VentaController@confirmar` fue intervenido. Ahora el cajero puede introducir el parámetro request `qr_code_pos`. 
- Si es válido, el backend inyecta automáticamente el descuento prefigurado (sea fijo, %, o el obsequio 2x1 extraído del valor mínimo).
- Marca el `escaneo_qr` como **`convertido = true`** y amarra la Venta ID al escaneo.

## Pruebas Manuales
1. Entrar como admin, ir a Menú > "Marketing QR"
2. Crear campaña de "Verano" con 10% Descuento.
3. Hacer click en generar/mostrar QR.
4. "Escanearlo" abriendo el link mostrado del UUID autogenerado en otra pestaña incógnito.
5. Verás el diseño de cupón azul y el ticket POS (Ej: `X9AK2`).
6. En el POS, cobrar una venta pasando el código `X9AK2`. El backend restará el 10% y la métrica de conversión de "Marketing" subirá a 1.
