# Contrato API: BenderAnd POS ↔ Bot de WhatsApp

BenderAnd POS utiliza una arquitectura basada en webhooks y llamadas HTTP directas para integrarse con bots de WhatsApp desarrollados en Node.js, Python o cualquier otro entorno capaz de enviar/recibir HTTP.

## 1. De Bot a BenderAnd POS (Webhooks)

El bot envía eventos a la API central de BenderAnd para registrar nuevos clientes (trials).

### POST `/webhook/whatsapp/onboarding`

Registra un nuevo negocio y provisiona automáticamente su base de datos aislada (Tenant).

**Headers Requeridos:**
- `Content-Type: application/json`

**Body Payload:**
```json
{
    "step": "complete",
    "nombre_empresa": "Ferretería Don Pedro",
    "rubro": "retail",
    "rut_empresa": "76123456-7",
    "whatsapp_admin": "56912345678",
    "email_admin": "pedro@ferreteria.cl",
    "password_admin": "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi",
    "nombre_admin": "Pedro Perez"
}
```

*Nota: `password_admin` debe venir ya encriptado en Bcrypt (o en texto plano según lo que espere el controlador local si decide encriptarlo en el alta).*

**Respuesta Exitosa (201 Created):**
```json
{
    "url": "https://ferreteria-don-pedro.benderand.cl",
    "estado": "trial",
    "dias_trial": 30
}
```

---

## 2. De BenderAnd POS al Bot (Send Message)

BenderAnd consume un HTTP server expuesto por el bot para enviarle mensajes (Comprobantes, Notificaciones, Alertas).

### POST `{WHATSAPP_BOT_URL}/send-message`

**Configuración en Laravel (`.env`):**
```env
WHATSAPP_BOT_URL="http://ip_del_bot:port"
WHATSAPP_BOT_TOKEN="token_secreto_para_auth"
```

**Headers Enviados por BenderAnd:**
- `Content-Type: application/json`
- `Authorization: Bearer {WHATSAPP_BOT_TOKEN}`

**Body Payload:**
```json
{
    "to": "56912345678",
    "message": "✅ *Comprobante BenderAnd POS*\n\nN° cd32-1..."
}
```

**Respuesta Esperada por BenderAnd:**
- El bot debe retornar Status `200 OK` si el mensaje fue puesto en cola exitosamente.
- El response body actualmente no se evalúa, sólo el código HTTP.

## 3. Catálogo de Eventos Salientes

Actualmente BenderAnd envía los siguientes mensajes al cliente o administrador:

1. **Comprobante de Venta (`comprobante`)**: Se dispara al presionar "Confirmar y Pagar". Envía el detalle.
2. **Stock Crítico (`stock_critico`)**: Se envía al `whatsapp_admin` cuando un movimiento reduce el inventario bajo el umbral `cantidad_minima`.
3. **Deuda Pendiente (`deuda_pendiente`)**: Tarea diaria a las 09:00 AM para cuentas sin pagar mayores a 7 días.
4. **Alerta Renta (`renta_venciendo`)**: 10 minutos antes del vencimiento configurado de una Renta (Ej. Motel, Pádel).
5. **Trial Expirando (`trial_expirando`)**: 7, 3 y 1 día antes del término del trial. Enviado al `whatsapp_admin`.
