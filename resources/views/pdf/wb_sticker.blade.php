<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Barcode PDF</title>
    <style>
        @page {
            size: 58mm 40mm; /* Установка размера страницы */
            margin: 0; /* Убираем отступы */
        }

        body {
            margin: 0; /* Убираем отступы */
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40mm; /* Высота страницы */
            width: 58mm; /* Ширина страницы */
        }

        img {
            max-height: 40mm; /* Максимальная высота для изображения */
            max-width: 58mm;  /* Максимальная ширина для изображения */
        }
    </style>
</head>
<body>
<img src="data:image/png;base64,{{ base64_encode(file_get_contents($imagePath)) }}" alt="Barcode">
</body>
</html>
