<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список товаров на осмотр</title>
    <style>
        @page {
            margin: 5mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h2 {
            margin: 0 0 10px 0;
            text-align: center;
        }

        .sticker-type {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .sticker-type td {
            padding: 5px;
            background: #f0f0f0;
            border: 1px solid #000;
        }

        .sticker-type td:first-child {
            text-align: left;
        }

        .sticker-type td:last-child {
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            text-align: center;
        }

        .number {
            text-align: center;
            width: 50px;
        }
    </style>
</head>
<body>
<h2>Товары на осмотр</h2>
<table class="sticker-type">
    <tr>
        <td>Тип стикера: {{ $stickerType === 'FBO' ? 'FBO' : 'Хранение' }}</td>
        <td>Всего товаров: {{ $items->count() }}</td>
    </tr>
</table>

<table>
    <thead>
    <tr>
        <th class="number">№</th>
        <th>Материал</th>
        <th>Ширина</th>
        <th>Высота</th>
    </tr>
    </thead>
    <tbody>
    @foreach($items as $index => $item)
        <tr>
            <td class="number">{{ $index + 1 }}</td>
            <td style="text-align: center">{{ $item->item->title }}</td>
            <td style="text-align: center">{{ $item->item->width }}</td>
            <td style="text-align: center">{{ $item->item->height }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
