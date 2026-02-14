<?php
/**
 * sgiT Education - 404 Not Found
 *
 * @version 1.0
 * @date 2026-02-14
 */
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Seite nicht gefunden</title>
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Space Grotesk', system-ui, sans-serif;
            background: linear-gradient(135deg, #0d1a02 0%, #1A3503 50%, #2d5a06 100%);
            color: #e0e0e0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            text-align: center;
            max-width: 500px;
        }
        .icon {
            font-size: 120px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        h1 {
            font-size: 72px;
            color: #43D240;
            margin-bottom: 10px;
            font-weight: 700;
        }
        h2 {
            font-size: 22px;
            color: #e0e0e0;
            margin-bottom: 20px;
            font-weight: 400;
        }
        p {
            color: #999;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #43D240, #35B035);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(67, 210, 64, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔭</div>
        <h1>404</h1>
        <h2>Seite nicht gefunden</h2>
        <p>Die Seite die du suchst existiert nicht oder wurde verschoben.</p>
        <a href="/" class="btn">Zur Startseite</a>
    </div>
</body>
</html>
