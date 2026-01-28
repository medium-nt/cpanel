<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ошибка 500</title>
    <style>
        body {
            font-family: sans-serif;
            text-align: center;
            padding: 60px;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            padding: 16px 32px;
            font-size: 20px;
            background: #3490dc;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<h1>Что‑то пошло не так...</h1>

<a href="{{ url()->previous() }}">← Назад</a>
</body>
</html>
