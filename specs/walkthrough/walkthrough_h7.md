# Walkthrough: Hito 7 - Configuración Dinámica por Industria (Rubros)

Se ha implementado el sistema de configuración atómica que permite adaptar la aplicación a 19 industrias diferentes de forma dinámica.

## Cambios Principales

### 1. Arquitectura de Base de Datos
- **Tabla `rubros_config`**: Almacena los módulos activos, etiquetas personalizadas (Vendedor vs Médico), colores de acento y configuraciones de flujo (ej. requiere RUT).
- **Tabla `bot_config`**: Almacena la configuración de personalidad y horarios del asistente de WhatsApp.

### 2. Presets de Industria
Se han cargado 19 presets mediante un seeder optimizado:
- **Salud**: Médico, Dentista, Veterinaria (Uso de "Pacientes", Módulos de Historial Médico).
- **Retail**: Abarrotes, Mayorista, Farmacia (Uso de Inventario, Códigos Rápidos).
- **Servicios**: Abogados, Técnicos, Taller (Módulos de OT, Agenda).
- **Hospitalidad**: Restaurante, Motel, Hotel (Módulos de Mesas, Reservas por Hora).

### 3. UI Dinámica
- **Menú Lateral**: Ahora se construye en tiempo real basado en `modulos_activos`. Si no eres un restaurante, no verás el módulo de Mesas.
- **Etiquetas**: El sistema reemplaza dinámicamente "Cliente" por "Paciente" o "Huésped" según el rubro.
- **POS**: Adaptado para mostrar labels correctos y tipos de producto específicos (Arriendo vs Servicio).

### 4. Herramientas de Gestión
- **Admin Config**: Nueva interfaz para cambiar de rubro manualmente y activar/desactivar módulos individuales.
- **Webhook Bot**: Endpoint listo para que el bot de WhatsApp pueda reconfigurar el negocio mediante comandos de voz/texto.

## Verificación Realizada

### Scripts de Servidor
Se ejecutó un script de verificación que demostró:
1. Carga correcta de los 19 presets.
2. Cambio exitoso de rubro (de `retail` a `medico`).
3. Actualización automática de etiquetas y módulos en la base de datos del tenant.

```bash
# Output de verificación
Rubro actual: Retail / Abarrotes (retail)
Módulos activos: M01, M02, M03, M04, M11, M12, M17, M18, M20, M24, M25, M32

Simulando aplicación de preset 'medico'...
Preset 'medico' aplicado.
Nuevo label cliente: Paciente
Módulos actuales: M01, M07, M08, M09, M10, M20, M21, M22, M23, M32
```

### Rutas API Verificadas
- `GET /api/config/rubro`: Devuelve la configuración activa.
- `POST /api/config/aplicar-preset/{id}`: Aplica un preset completo.
- `POST /webhook/wa/config`: Recibe intenciones del bot.
