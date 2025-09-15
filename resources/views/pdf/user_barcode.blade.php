<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Barcode PDF</title>
    <style>
        body {
            margin: 0; /* Убираем отступы */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            padding: 0 5px ; /* или укажи в px, например: 20px */
            text-align: center;
            box-sizing: border-box;
        }

        .barcode {
            max-width: 100%;
            height: auto;
            width: 100%;
            padding: 30px 5px; /* немного отступов по краям, чтобы сканер видел quiet zone */
            box-sizing: border-box;
            text-align: center;
        }

        .code-label {
            font-family: Helvetica, sans-serif;
            font-size: 25px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="barcode">
            {!! DNS1D::getBarcodeHTML($user->role_id . '-' . $user->id . '-' . $user->created_at->format('Ymd'), 'C128', 6.0, 250) !!}
        </div>
        <div class="code-label">{{ $user->name }}</div>
    </div>
</body>
</html>
