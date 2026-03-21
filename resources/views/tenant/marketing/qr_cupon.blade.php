<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $campana->nombre }} - {{ tenant('id') }}</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; margin: 0; padding: 20px; display: flex; justify-content: center; min-height: 100vh; align-items: center; }
        .coupon-card { background: white; border-radius: 16px; padding: 30px; text-align: center; max-width: 400px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .title { font-size: 24px; font-weight: 800; color: #111827; margin-bottom: 10px; }
        .desc { font-size: 16px; color: #4B5563; margin-bottom: 30px; line-height: 1.5; }
        .discount-code { background: #eef2ff; color: #4338ca; padding: 20px; border-radius: 12px; font-size: 32px; font-weight: 900; letter-spacing: 2px; border: 2px dashed #a5b4fc; margin-bottom: 20px; }
        .instruction { font-size: 14px; color: #6B7280; margin-bottom: 0; }
        .cta-btn { display: inline-block; background: #4338ca; color: white; padding: 12px 24px; border-radius: 8px; font-weight: bold; text-decoration: none; margin-top: 20px; transition: 0.2s; border: none; width: 100%; cursor: pointer; }
        .cta-btn:hover { background: #3730A3; transform: scale(1.02); }
    </style>
</head>
<body>
    <div class="coupon-card">
        <h1 class="title">{{ $campana->nombre }}</h1>
        <div class="desc">{{ $campana->descripcion ?? '¡Muestra este código al momento de pagar para obtener tu beneficio!' }}</div>
        
        @if(in_array($campana->tipo_accion, ['descuento_porcentaje', 'descuento_fijo', 'dos_por_uno']))
            <div class="instruction">Código para validación en caja:</div>
            <div class="discount-code">{{ $campana->codigo_pos }}</div>
            
            @if($campana->tipo_accion === 'descuento_porcentaje')
                <div style="font-size: 20px; font-weight: bold; color: #10B981; margin-bottom: 20px;">-{{ $campana->valor_descuento }}% de Descuento</div>
            @elseif($campana->tipo_accion === 'descuento_fijo')
                <div style="font-size: 20px; font-weight: bold; color: #10B981; margin-bottom: 20px;">-${{ number_format($campana->valor_descuento, 0, ',', '.') }} de Descuento</div>
            @elseif($campana->tipo_accion === 'dos_por_uno')
                <div style="font-size: 20px; font-weight: bold; color: #10B981; margin-bottom: 20px;">2x1 en tu compra</div>
            @endif

            <p style="font-size: 12px; color: #9CA3AF; margin-top:20px;">Válido hasta: {{ $campana->fecha_fin ? $campana->fecha_fin->format('d/m/Y') : 'Sin expiración' }}</p>
        @else
            <!-- Fallback for other action types if they ended up here by error -->
            <button class="cta-btn" onclick="window.history.back()">Volver</button>
        @endif
    </div>
</body>
</html>
