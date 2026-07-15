<!DOCTYPE html>
<html lang="ru">

<head>
    <title>Доска рейтинга</title>
    <meta charset="UTF-8">
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('rating_board/css/style.css') }}">
</head>

<body data-token="{{ $token }}" data-workshop="{{ $workshop }}">
<audio id="click-sound"
       src="{{ asset('rating_board/files/1_место_занял.mp3') }}"
       preload="auto"></audio>
<audio id="sound-fbo" src="{{ asset('rating_board/files/ФБО.mp3') }}"
       preload="auto"></audio>
<audio id="sound-fbs" src="{{ asset('rating_board/files/ФБС.mp3') }}"
       preload="auto"></audio>
<audio id="sound-shift-open"
       src="{{ asset('rating_board/files/Смена_открыта.mp3') }}"
       preload="auto"></audio>
<audio id="sound-shift-warning"
       src="{{ asset('rating_board/files/закрытие_смены.mp3') }}"
       preload="auto"></audio>
<audio id="sound-shift-close"
       src="{{ asset('rating_board/files/смена_закрыта.mp3') }}"
       preload="auto"></audio>
<main class="wrapper">
    <section class="hero">
        <div class="hero__container">
            <div class="hero__body">
                <div class="hero__column">
                    <div class="leaders">
                        <h2 class="leaders__title">Таблица лидеров</h2>
                        <div class="leaders__items" id="leaders-container">
                            <!-- Данные загружаются через polling -->
                        </div>
                    </div>
                </div>
                <div class="hero__column">
                    <div class="top">
                        <h1 class="top__title"></h1>
                        <div class="top__body" id="top-leaderboard">
                            <!-- Подиум загружается через polling -->
                        </div>
                    </div>
                    <div class="stickers">
                        <h2 class="stickers__title">На стикеровке</h2>
                        <div class="stickers__body">
                            <div id="sticker-fbo" class="stickers__item">
                                FBO:<span>0</span></div>
                            <div id="sticker-fbs" class="stickers__item">
                                FBS:<span>0</span></div>
                        </div>
                    </div>
                </div>
                <div class="hero__column">
                    <div class="statistics">
                        @php
                            $months = [1 => 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
                        @endphp
                        <h2 class="statistics__title">Статистика
                            за {{ $months[now()->month] }}</h2>
                        <div class="statistics__carousel">
                            <div class="statistics__items">
                                <!-- Статистика загружается через polling -->
                            </div>
                        </div>
                    </div>
                    <div class="winner">
                        <h2 class="winner__title"></h2>
                        <div class="winner__row">
                            <div class="winner__icon">
                                <img
                                    src="{{ asset('rating_board/img/icon.webp') }}"
                                    alt="Мишень">
                            </div>
                            <div class="winner__text">
                                <p></p>
                                <p class="winner__date"></p>
                            </div>
                        </div>
                        <h2 class="winner__title">Победитель смены</h2>
                        <div class="winner__body">
                            <div class="winner__avatar">
                                <img src="" alt="">
                            </div>
                            <div class="winner__name"></div>
                        </div>
                        <div class="winner__desc"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<div id="popup-morning" aria-hidden="true" class="popup">
    <div class="popup__wrapper">
        <div class="popup__content">
            <div class="popup__icon">
                <img src="{{ asset('rating_board/img/popup-icon.webp') }}"
                     alt="">
            </div>
            <div class="popup__text">
                До открытия смены осталось: <span id="morning-timer"
                                                  style="font-weight: bold;">10:00:00</span>
            </div>
        </div>
    </div>
</div>

<div id="popup-evening" aria-hidden="true" class="popup">
    <div class="popup__wrapper">
        <div class="popup__content">
            <div class="popup__icon">
                <img src="{{ asset('rating_board/img/popup-icon.webp') }}"
                     alt="">
            </div>
            <div class="popup__text" id="evening-text-block">До закрытия смены
                осталось:
                <span id="evening-timer">30:00:00</span>
            </div>
        </div>
    </div>
</div>


<script>window.RATING_BOARD_IMG = "{{ asset('rating_board/img') }}/";</script>
<script src="{{ asset('rating_board/js/gsap.min.js') }}"></script>
<script src="{{ asset('rating_board/js/Flip.min.js') }}"></script>
<script src="{{ asset('rating_board/js/app.js') }}"></script>
</body>

</html>
