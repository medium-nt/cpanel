<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css"/>

    <style>
        .bg-gray-100 {
            margin-bottom: 0 !important;
        }
</style>
</head>
<body class="flex h-screen w-screen items-center justify-center bg-gray-100">
    <div class="w-full max-w-md px-8 py-6 mx-auto bg-white border border-gray-200 rounded-lg shadow-md">
        <form action="{{ route('login') }}" method="GET" class="space-y-6">
            <h2 class="text-2xl font-bold text-center mb-3">Войти в систему</h2>

            <a href="{{ route('login') }}" class="w-full inline-block py-3 text-center text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 leading-tight">Войти</a>

            @if (Illuminate\Support\Facades\App::environment(['local']))
                <div class="mb-1">
                    <a href="{{ route('users.autologin', ['email' => '1@1.ru']) }}" class="mr-3 bg-gray-100 text-gray-500">Админ</a>
                    <a href="{{ route('users.autologin', ['email' => '2@2.ru']) }}" class="mr-3 bg-gray-100 text-gray-500" >Кладовщик</a>
                    <a href="{{ route('users.autologin', ['email' => '3@3.ru']) }}" class="mr-3 bg-gray-100 text-gray-500" >Швея</a>
                    <a href="{{ route('users.autologin', ['email' => '4@4.ru']) }}" class="mr-3 bg-gray-100 text-gray-500" >Закройщик</a>
                    <a href="{{ route('users.autologin', ['email' => '5@5.ru']) }}"
                       class="mr-3 bg-gray-100 text-gray-500">ОТК</a>
                </div>
            @endif
        </form>
    </div>
</body>
</html>
