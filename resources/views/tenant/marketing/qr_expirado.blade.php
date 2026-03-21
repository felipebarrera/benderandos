<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaña Expirada - {{ tenant('id') }}</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; margin: 0; padding: 20px; display: flex; justify-content: center; min-height: 100vh; align-items: center; }
        .coupon-card { background: white; border-radius: 16px; padding: 40px 30px; text-align: center; max-width: 400px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .icon { font-size: 48px; margin-bottom: 20px; }
        .title { font-size: 22px; font-weight: 800; color: #111827; margin-bottom: 10px; }
        .desc { font-size: 16px; color: #4B5563; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="coupon-card">
        <div class="icon">⌛</div>
        <h1 class="title">Campaña {{ $campana?->nombre }} finalizada</h1>
        <div class="desc">Lo sentimos, esta campaña ya no se encuentra activa o ha superado su límite de usos. ¡Mantente atento a nuevas promociones!</div>
    </div>
</body>
</html>
