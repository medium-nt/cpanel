<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="stylesheet"
          href="{{ asset('vendor/tailwind/tailwind.min.css') }}"/>

    <style>
        .bg-gray-100 {
            margin-bottom: 0 !important;
        }
</style>
</head>
<body class="flex flex-col h-screen w-screen bg-gray-100">
<div class="flex-1 flex items-center justify-center">
    <div
        class="w-full max-w-md px-8 py-6 mx-auto bg-white border border-gray-200 rounded-lg shadow-md">
        <form action="{{ route('login') }}" method="GET" class="space-y-6">
            <h2 class="text-2xl font-bold text-center mb-3">Войти в систему</h2>

            <a href="{{ route('login') }}"
               class="w-full inline-block py-3 text-center text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 leading-tight">Войти</a>

            @if (Illuminate\Support\Facades\App::environment(['local']))
                <div class="mb-1">
                    <a href="{{ route('users.autologin', ['email' => '1@1.ru']) }}"
                       class="mr-3 bg-gray-100 text-gray-500">Админ</a>
                    <a href="{{ route('users.autologin', ['email' => '2@2.ru']) }}"
                       class="mr-3 bg-gray-100 text-gray-500">Кладовщик</a>
                    <a href="{{ route('users.autologin', ['email' => '3@3.ru']) }}"
                       class="mr-3 bg-gray-100 text-gray-500">Швея</a>
                    <a href="{{ route('users.autologin', ['email' => '4@4.ru']) }}"
                       class="mr-3 bg-gray-100 text-gray-500">Закройщик</a>
                    <a href="{{ route('users.autologin', ['email' => '5@5.ru']) }}"
                       class="mr-3 bg-gray-100 text-gray-500">ОТК</a>
                    <a href="{{ route('users.autologin', ['email' => '6@6.ru']) }}"
                       class="mr-3 bg-gray-100 text-gray-500">Водитель</a>
                    <a href="{{ route('users.autologin', ['email' => '7@7.ru']) }}"
                       class="mr-3 bg-gray-100 text-gray-500">Менеджер</a>
                </div>
            @endif
        </form>
    </div>
</div>

<footer class="py-4 text-center text-xs text-gray-400">
    <p class="font-medium text-gray-500">Система управления швейного
        производства</p>
    <p class="mt-1">ИНН: 760218194200 &middot; ОГРН: 322774600341432</p>
    <p>ИП Левкин А.С. &middot; Тел: +79997863525</p>
</footer>
</body>
</html>
