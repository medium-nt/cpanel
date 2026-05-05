<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Стикер смены</title>
    <style>
        @page {
            size: 58mm 40mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
        }

        .page {
            text-align: center;
            padding-top: 13mm;
        }

        .shift-name {
            font-weight: bold;
            font-size: 24px;
        }
    </style>
</head>
<body>
@for($i = 0; $i < $count; $i++)
    <div class="page">
        <div class="shift-name">{{ $shiftName }}</div>
    </div>
@endfor
</body>
</html>
