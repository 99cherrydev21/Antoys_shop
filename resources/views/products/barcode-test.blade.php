<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Data Matrix - Antoys</title>
</head>
<body>
    <h1>Prueba de Data Matrix</h1>

    <p>SKU: {{ $sku }}</p>

    <div>
        <img
            src="data:image/png;base64,{{ $codigo }}"
            alt="Data Matrix del producto {{ $sku }}"
            style="width: 200px; height: 200px; image-rendering: pixelated;"
        >
    </div>

    <p>Este código contiene únicamente el SKU del producto.</p>

    <button onclick="window.print()">Imprimir código</button>
</body>
</html>
