document.addEventListener("DOMContentLoaded", function (event) {

    // Демо-обработчики удалены (Этап 4): данные идут из JSON-эндпоинта по polling.
    // Polling-движок и рендер определены в конце файла.


    let bodyLockStatus = true;
    let bodyLockToggle = (delay = 500) => {
        if (document.documentElement.classList.contains('lock')) {
            bodyUnlock(delay);
        } else {
            bodyLock(delay);
        }
    }
    let bodyUnlock = (delay = 500) => {
        let body = document.querySelector("body");
        if (bodyLockStatus) {
            let lock_padding = document.querySelectorAll("[data-lp]");
            setTimeout(() => {
                for (let index = 0; index < lock_padding.length; index++) {
                    const el = lock_padding[index];
                    el.style.paddingRight = '0px';
                }
                body.style.paddingRight = '0px';
                document.documentElement.classList.remove("lock");
            }, delay);
            bodyLockStatus = false;
            setTimeout(function () {
                bodyLockStatus = true;
            }, delay);
        }
    }
    let bodyLock = (delay = 500) => {
        let body = document.querySelector("body");
        if (bodyLockStatus) {
            let lock_padding = document.querySelectorAll("[data-lp]");
            for (let index = 0; index < lock_padding.length; index++) {
                const el = lock_padding[index];
                el.style.paddingRight = window.innerWidth - document.querySelector('.wrapper').offsetWidth + 'px';
            }
            body.style.paddingRight = window.innerWidth - document.querySelector('.wrapper').offsetWidth + 'px';
            document.documentElement.classList.add("lock");

            bodyLockStatus = false;
            setTimeout(function () {
                bodyLockStatus = true;
            }, delay);
        }
    }

    let isMobile = {
        Android: function () {
            return navigator.userAgent.match(/Android/i);
        }, BlackBerry: function () {
            return navigator.userAgent.match(/BlackBerry/i);
        }, iOS: function () {
            return navigator.userAgent.match(/iPhone|iPad|iPod/i);
        }, Opera: function () {
            return navigator.userAgent.match(/Opera Mini/i);
        }, Windows: function () {
            return navigator.userAgent.match(/IEMobile/i);
        }, any: function () {
            return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
        }
    };

    // Класс Popup
    class Popup {
        constructor(options) {
            let config = {
                logging: true,
                init: true,
                //Для кнопок
                attributeOpenButton: 'data-popup', // Атрибут для кнопки, которая вызывает попап
                attributeCloseButton: 'data-close', // Атрибут для кнопки, которая закрывает попап
                // Для сторонніх об'єктів
                fixElementSelector: '[data-lp]', // Атрибут для элементов с левым паддингом (которые fixed)
                // Для об'єкту попапа
                youtubeAttribute: 'data-popup-youtube', // Атрибут для кода youtube
                youtubePlaceAttribute: 'data-popup-youtube-place', // Атрибут для вставки ролика youtube
                setAutoplayYoutube: true,
                // Изменение классов
                classes: {
                    popup: 'popup',
                    // popupWrapper: 'popup__wrapper',
                    popupContent: 'popup__content',
                    popupActive: 'popup_show', // Добавляется для попапа, когда он открывается
                    bodyActive: 'popup-show', // Добавляется для боди, когда попап открыт
                },
                focusCatch: true, // Фокус внутри попапа зациклен
                closeEsc: true, // Закрытие по ESC
                bodyLock: true, // Блокировка скролла
                hashSettings: {
                    location: true, // Хэш в адресной строке
                    goHash: true, // Переход по наличию в адресной строке
                },
                on: { // События
                    beforeOpen: function () {
                    },
                    afterOpen: function () {
                    },
                    beforeClose: function () {
                    },
                    afterClose: function () {
                    },
                },
            }
            this.youTubeCode;
            this.isOpen = false;
            // Текущее окно
            this.targetOpen = {
                selector: false,
                element: false,
            }
            // Предыдущее открытое
            this.previousOpen = {
                selector: false,
                element: false,
            }
            // Последнее закрытое
            this.lastClosed = {
                selector: false,
                element: false,
            }
            this._dataValue = false;
            this.hash = false;

            this._reopen = false;
            this._selectorOpen = false;

            this.lastFocusEl = false;
            this._focusEl = [
                'a[href]',
                'input:not([disabled]):not([type="hidden"]):not([aria-hidden])',
                'button:not([disabled]):not([aria-hidden])',
                'select:not([disabled]):not([aria-hidden])',
                'textarea:not([disabled]):not([aria-hidden])',
                'area[href]',
                'iframe',
                'object',
                'embed',
                '[contenteditable]',
                '[tabindex]:not([tabindex^="-"])'
            ];
            //this.options = Object.assign(config, options);
            this.options = {
                ...config,
                ...options,
                classes: {
                    ...config.classes,
                    ...options?.classes,
                },
                hashSettings: {
                    ...config.hashSettings,
                    ...options?.hashSettings,
                },
                on: {
                    ...config.on,
                    ...options?.on,
                }
            }
            this.bodyLock = false;
            this.options.init ? this.initPopups() : null
        }

        initPopups() {
            this.eventsPopup();
        }

        eventsPopup() {
            // Клик на всем документе
            document.addEventListener("click", function (e) {
                // Клик по кнопке "открыть"
                const buttonOpen = e.target.closest(`[${this.options.attributeOpenButton}]`);
                if (buttonOpen) {
                    e.preventDefault();
                    this._dataValue = buttonOpen.getAttribute(this.options.attributeOpenButton) ?
                        buttonOpen.getAttribute(this.options.attributeOpenButton) :
                        'error';
                    this.youTubeCode = buttonOpen.getAttribute(this.options.youtubeAttribute) ?
                        buttonOpen.getAttribute(this.options.youtubeAttribute) :
                        null;
                    if (this._dataValue !== 'error') {
                        if (!this.isOpen) this.lastFocusEl = buttonOpen;
                        this.targetOpen.selector = `${this._dataValue}`;
                        this._selectorOpen = true;
                        this.open();
                        return;

                    }

                    return;
                }
                // Закрытие на пустом месте (popup__wrapper) и кнопки закрытия (popup__close) для закрытия
                const buttonClose = e.target.closest(`[${this.options.attributeCloseButton}]`);
                if (buttonClose || !e.target.closest(`.${this.options.classes.popupContent}`) && this.isOpen) {
                    e.preventDefault();
                    this.close();
                    return;
                }
            }.bind(this));
            // Закрытие по ESC
            document.addEventListener("keydown", function (e) {
                if (this.options.closeEsc && e.which == 27 && e.code === 'Escape' && this.isOpen) {
                    e.preventDefault();
                    this.close();
                    return;
                }
                if (this.options.focusCatch && e.which == 9 && this.isOpen) {
                    this._focusCatch(e);
                    return;
                }
            }.bind(this))

            // Открытие по хешу
            if (this.options.hashSettings.goHash) {
                // Проверка изменения адресной строки
                window.addEventListener('hashchange', function () {
                    if (window.location.hash) {
                        this._openToHash();
                    } else {
                        this.close(this.targetOpen.selector);
                    }
                }.bind(this))

                window.addEventListener('load', function () {
                    if (window.location.hash) {
                        this._openToHash();
                    }
                }.bind(this))
            }
        }

        open(selectorValue) {
            if (bodyLockStatus) {
                // Если перед открытием попапа был режим lock
                this.bodyLock = document.documentElement.classList.contains('lock') && !this.isOpen ? true : false;

                // Если ввести значение селектора (селектор настраивается в options)
                if (selectorValue && typeof (selectorValue) === "string" && selectorValue.trim() !== "") {
                    this.targetOpen.selector = selectorValue;
                    this._selectorOpen = true;
                }
                if (this.isOpen) {
                    this._reopen = true;
                    this.close();
                }
                if (!this._selectorOpen) this.targetOpen.selector = this.lastClosed.selector;
                if (!this._reopen) this.previousActiveElement = document.activeElement;

                this.targetOpen.element = document.querySelector(this.targetOpen.selector);

                if (this.targetOpen.element) {
                    // YouTube
                    if (this.youTubeCode) {
                        const codeVideo = this.youTubeCode;
                        const urlVideo = `https://www.youtube.com/embed/${codeVideo}?rel=0&showinfo=0&autoplay=1`
                        const iframe = document.createElement('iframe');
                        iframe.setAttribute('allowfullscreen', '');

                        const autoplay = this.options.setAutoplayYoutube ? 'autoplay;' : '';
                        iframe.setAttribute('allow', `${autoplay}; encrypted-media`);

                        iframe.setAttribute('src', urlVideo);

                        if (!this.targetOpen.element.querySelector(`[${this.options.youtubePlaceAttribute}]`)) {
                            const youtubePlace = this.targetOpen.element.querySelector('.popup__text').setAttribute(`${this.options.youtubePlaceAttribute}`, '');
                        }
                        this.targetOpen.element.querySelector(`[${this.options.youtubePlaceAttribute}]`).appendChild(iframe);
                    }
                    if (this.options.hashSettings.location) {
                        // Получение хэша и его выставление
                        this._getHash();
                        this._setHash();
                    }

                    // До открытия
                    this.options.on.beforeOpen(this);
                    // Создаем свое событие после открытия попапа
                    document.dispatchEvent(new CustomEvent("beforePopupOpen", {
                        detail: {
                            popup: this
                        }
                    }));

                    this.targetOpen.element.classList.add(this.options.classes.popupActive);
                    document.documentElement.classList.add(this.options.classes.bodyActive);

                    if (!this._reopen) {
                        !this.bodyLock ? bodyLock() : null;
                    } else this._reopen = false;

                    this.targetOpen.element.setAttribute('aria-hidden', 'false');

                    // Запоминаю это открытое окно. Оно будет последним открытым
                    this.previousOpen.selector = this.targetOpen.selector;
                    this.previousOpen.element = this.targetOpen.element;

                    this._selectorOpen = false;

                    this.isOpen = true;

                    setTimeout(() => {
                        this._focusTrap();
                    }, 50);

                    // После открытия
                    this.options.on.afterOpen(this);
                    // Создаем свое событие после открытия попапа
                    document.dispatchEvent(new CustomEvent("afterPopupOpen", {
                        detail: {
                            popup: this
                        }
                    }));

                }
            }
        }

        close(selectorValue) {
            if (selectorValue && typeof (selectorValue) === "string" && selectorValue.trim() !== "") {
                this.previousOpen.selector = selectorValue;
            }
            if (!this.isOpen || !bodyLockStatus) {
                return;
            }
            // До закрытия
            this.options.on.beforeClose(this);
            // Создаем свое событие перед закрытием попапа
            document.dispatchEvent(new CustomEvent("beforePopupClose", {
                detail: {
                    popup: this
                }
            }));

            // YouTube
            if (this.youTubeCode) {
                if (this.targetOpen.element.querySelector(`[${this.options.youtubePlaceAttribute}]`))
                    this.targetOpen.element.querySelector(`[${this.options.youtubePlaceAttribute}]`).innerHTML = '';
            }
            this.previousOpen.element.classList.remove(this.options.classes.popupActive);
            // aria-hidden
            this.previousOpen.element.setAttribute('aria-hidden', 'true');
            if (!this._reopen) {
                document.documentElement.classList.remove(this.options.classes.bodyActive);
                !this.bodyLock ? bodyUnlock() : null;
                this.isOpen = false;
            }
            // Очищение адресной строки
            this._removeHash();
            if (this._selectorOpen) {
                this.lastClosed.selector = this.previousOpen.selector;
                this.lastClosed.element = this.previousOpen.element;

            }
            // После закрытия
            this.options.on.afterClose(this);
            // Создаем свое событие после закрытия попапа
            document.dispatchEvent(new CustomEvent("afterPopupClose", {
                detail: {
                    popup: this
                }
            }));

            setTimeout(() => {
                this._focusTrap();
            }, 50);

        }

        // Получение хэша
        _getHash() {
            if (this.options.hashSettings.location) {
                this.hash = this.targetOpen.selector.includes('#') ?
                    this.targetOpen.selector : this.targetOpen.selector.replace('.', '#')
            }
        }

        _openToHash() {
            let classInHash = null;

            // Поиск класса в hash
            if (window.location.hash) {
                classInHash = document.querySelector(`.${window.location.hash.replace('#', '')}`)
                    ? `.${window.location.hash.replace('#', '')}`
                    : document.querySelector(`${window.location.hash}`)
                        ? `${window.location.hash}`
                        : null;
            }

            if (!classInHash) return; // Если hash не найден, выходим из функции

            // Поиск кнопок по атрибуту
            let buttons = document.querySelector(`[${this.options.attributeOpenButton} = "${classInHash}"]`)
                || document.querySelector(`[${this.options.attributeOpenButton} = "${classInHash.replace('.', "#")}"]`);

            if (!buttons) return; // Если кнопка не найдена, выходим из функции

            // Получение кода YouTube, если он есть
            this.youTubeCode = buttons.getAttribute(this.options.youtubeAttribute)
                ? buttons.getAttribute(this.options.youtubeAttribute)
                : null;

            // Открытие, если кнопка и classInHash найдены
            if (classInHash) this.open(classInHash);
        }

        // Утсановка хэша
        _setHash() {
            history.pushState('', '', this.hash);
        }

        _removeHash() {
            history.pushState('', '', window.location.href.split('#')[0])
        }

        _focusCatch(e) {
            const focusable = this.targetOpen.element.querySelectorAll(this._focusEl);
            const focusArray = Array.prototype.slice.call(focusable);
            const focusedIndex = focusArray.indexOf(document.activeElement);

            if (e.shiftKey && focusedIndex === 0) {
                focusArray[focusArray.length - 1].focus();
                e.preventDefault();
            }
            if (!e.shiftKey && focusedIndex === focusArray.length - 1) {
                focusArray[0].focus();
                e.preventDefault();
            }
        }

        _focusTrap() {
            const focusable = this.previousOpen.element.querySelectorAll(this._focusEl);
            if (!this.isOpen && this.lastFocusEl) {
                this.lastFocusEl.focus();
            }
        }
    }

    // Запускаем и добавляем в переменную
    let popupItem = new Popup({});
    //popupItem.open('#popup');


    gsap.registerPlugin(Flip);

    const leaderboard = document.getElementById('top-leaderboard');
    const clickSound = document.getElementById('click-sound');

    // Сила перелёта (overshoot).
    const overshootStrength = 1.2;

    // 1. АНИМАЦИЯ: Смена Золото и Серебро (Звёздочка загорается у ЗОЛОТА)
    function swapGoldSilver() {
        if (!leaderboard) return;

        const silverItem = leaderboard.querySelector('.top__item--silver');
        const goldItem = leaderboard.querySelector('.top__item--gold');
        const avatarSilver = silverItem.querySelector('.top__avatar');
        const avatarGold = goldItem.querySelector('.top__avatar');

        if (!avatarSilver || !avatarGold) return;

        const state = Flip.getState([avatarSilver, avatarGold]);

        silverItem.prepend(avatarGold);
        goldItem.prepend(avatarSilver);

        if (clickSound) {
            clickSound.currentTime = 0;
            clickSound.play();
        }

        Flip.from(state, {
            duration: 0.6,
            ease: `back.out(${overshootStrength})`,
            absolute: true,
            onComplete: () => {
                // Взрываем звёздочку на 1 месте (Золото)
                const star = goldItem.querySelector('.top__star');
                if (star) {
                    gsap.fromTo(star,
                        {scale: 0, opacity: 0},
                        {
                            scale: 1.3,
                            opacity: 1,
                            duration: 0.3,
                            yoyo: true,
                            repeat: 1
                        }
                    );
                }
            }
        });
    }

    // 2. АНИМАЦИЯ: Смена Серебро и Бронза (Звёздочка загорается у СЕРЕБРА)
    function swapSilverBronze() {
        if (!leaderboard) return;

        const silverItem = leaderboard.querySelector('.top__item--silver');
        const bronzeItem = leaderboard.querySelector('.top__item--bronze');
        const avatarSilver = silverItem.querySelector('.top__avatar');
        const avatarBronze = bronzeItem.querySelector('.top__avatar');

        if (!avatarSilver || !avatarBronze) return;

        const state = Flip.getState([avatarSilver, avatarBronze]);

        silverItem.prepend(avatarBronze);
        bronzeItem.prepend(avatarSilver);

        Flip.from(state, {
            duration: 0.6,
            ease: `back.out(${overshootStrength})`,
            absolute: true,
            onComplete: () => {
                // Когда прилетел новый человек на 2 место (Серебро), взрываем звёздочку там
                const star = silverItem.querySelector('.top__star');
                if (star) {
                    gsap.fromTo(star,
                        {scale: 0, opacity: 0},
                        {
                            scale: 1.3,
                            opacity: 1,
                            duration: 0.3,
                            yoyo: true,
                            repeat: 1
                        }
                    );
                }
            }
        });
    }

    // 3. АНИМАЦИЯ: Новый на Бронзу (Звёздочка загорается у БРОНЗЫ)
    function replaceBronzePlace(newAvatarUrl, newId) {
        if (!leaderboard) return;

        const bronzeItem = leaderboard.querySelector('.top__item--bronze');
        const avatarBronze = bronzeItem.querySelector('.top__avatar');

        if (!avatarBronze) return;

        // Старая аватарка улетает
        gsap.to(avatarBronze, {
            y: 50,
            x: 50,
            opacity: 0,
            duration: 0.35,
            ease: "power2.in",
            onComplete: () => {
                // Подменяем данные
                const img = avatarBronze.querySelector('img');
                img.src = newAvatarUrl;
                avatarBronze.setAttribute('data-id', newId);

                // Проявляем новую аватарку через scale
                gsap.fromTo(avatarBronze,
                    {y: 0, x: 0, scale: 0, opacity: 0},
                    {
                        scale: 1,
                        opacity: 1,
                        duration: 0.45,
                        ease: `back.out(${overshootStrength})`,
                        onComplete: () => {
                            // Как только новая аватарка зафиксировалась на 3 месте, взрываем звёздочку Бронзы
                            const star = bronzeItem.querySelector('.top__star');
                            if (star) {
                                gsap.fromTo(star,
                                    {scale: 0, opacity: 0},
                                    {
                                        scale: 1.3,
                                        opacity: 1,
                                        duration: 0.3,
                                        yoyo: true,
                                        repeat: 1
                                    }
                                );
                            }
                        }
                    }
                );
            }
        });
    }

    // Функция плавной смены чисел в стикерах со своим звуком для каждого типа
    function animateStickerValue(stickerId, newValue) {
        const sticker = document.getElementById(stickerId);
        if (!sticker) return;

        const valueSpan = sticker.querySelector('span');
        if (!valueSpan) return;

        // Считываем старое значение и переводим в число (на случай, если там пустой текст — ставим 0)
        const oldValue = parseFloat(valueSpan.textContent) || 0;

        // ОПРЕДЕЛЯЕМ ЗВУК: проверяем, какой именно ID к нам пришел
        let currentSound;
        if (stickerId === 'sticker-fbo') {
            currentSound = document.getElementById('sound-fbo');
        } else if (stickerId === 'sticker-fbs') {
            currentSound = document.getElementById('sound-fbs');
        }

        // 1. Измеряем стартовую ширину текущей цифры
        const startWidth = valueSpan.offsetWidth;
        valueSpan.style.width = startWidth + 'px';

        // 2. Уменьшаем и скрываем СТАРУЮ цифру
        gsap.to(valueSpan, {
            scale: 0,
            opacity: 0,
            duration: 0.25,
            ease: "power2.in",
            onComplete: () => {
                // 3. Старая цифра скрылась. Меняем текст на новое число
                valueSpan.textContent = newValue;

                // Измеряем, какая ширина нужна для НОВОЙ цифры
                valueSpan.style.width = 'auto';
                const endWidth = valueSpan.offsetWidth;

                // Возвращаем стартовую ширину и скейл в 0 перед началом роста
                valueSpan.style.width = startWidth + 'px';
                gsap.set(valueSpan, {scale: 0, opacity: 0});

                // --- ВОСПРОИЗВЕДЕНИЕ ЗВУКА С ПРОВЕРКОЙ НА РОСТ ---
                // Звук включится, только если новое значение строго больше старого
                if (currentSound && newValue > oldValue) {
                    currentSound.currentTime = 0; // Сбрасываем в начало
                    currentSound.play().catch(e => console.log("Audio play blocked:", e));
                }

                // 4. Запускаем одновременно рост цифры и плавное изменение ширины
                gsap.to(valueSpan, {
                    width: endWidth + 'px',
                    duration: 0.55,
                    ease: "back.out(1.2)"
                });

                gsap.to(valueSpan, {
                    scale: 1,
                    opacity: 1,
                    duration: 0.55,
                    ease: "back.out(1.2)",
                    onComplete: () => {
                        valueSpan.style.width = '';
                    }
                });
            }
        });
    }

    // Функция инициализации бесконечного автоскролла статистики
    function initStatisticsScroll() {
        const carousel = document.querySelector('.statistics__carousel');
        const itemsContainer = document.querySelector('.statistics__items');

        if (!carousel || !itemsContainer) return;

        gsap.killTweensOf(itemsContainer);
        gsap.set(itemsContainer, {clearProps: "transform"});

        const carouselHeight = carousel.offsetHeight;
        const itemsHeight = itemsContainer.offsetHeight;

        if (itemsHeight <= carouselHeight) {
            return;
        }

        const originalItems = itemsContainer.innerHTML;
        itemsContainer.innerHTML = originalItems + originalItems;

        const speed = 30;
        const duration = itemsHeight / speed;

        gsap.to(itemsContainer, {
            yPercent: -50,
            duration: duration,
            ease: "none",
            repeat: -1
        });
    }

    // Скролл статистики запускается из renderInitial (Этап 4) после построения DOM из JSON.
    // window.addEventListener('load', initStatisticsScroll);


    let morningCountdown;
    let eveningCountdown;

    // Помощник для форматирования времени (секунды -> ЧЧ:ММ:СС)
    function formatTime(totalSeconds) {
        const hrs = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
        const mins = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
        const secs = String(totalSeconds % 60).padStart(2, '0');
        return `${hrs}:${mins}:${secs}`;
    }

    // ========================================================
    // 1. УТРЕННИЙ ПОПАП (10 минут до открытия)
    // ========================================================
    function initMorningShift(secondsLeft) {
        const morningTimer = document.getElementById('morning-timer');
        let timeLeft = Math.max(0, secondsLeft | 0);

        if (typeof popupItem !== 'undefined') {
            popupItem.open('#popup-morning');
        }

        clearInterval(morningCountdown);
        morningCountdown = setInterval(() => {
            timeLeft--;
            if (morningTimer) morningTimer.textContent = formatTime(timeLeft);

            if (timeLeft <= 0) {
                clearInterval(morningCountdown);

                // Играем звук "Смена открыта"
                const soundOpen = document.getElementById('sound-shift-open');
                if (soundOpen) {
                    soundOpen.currentTime = 0;
                    soundOpen.play();
                }

                // Просто закрываем утренний попап
                if (typeof popupItem !== 'undefined' && popupItem.close) {
                    popupItem.close('#popup-morning');
                }
            }
        }, 1000);
    }

    // ========================================================
    // 2. ВЕЧЕРНИЙ ПОПАП (30 минут до закрытия)
    // ========================================================
    function triggerEveningWarning(secondsLeft) {
        const eveningTimer = document.getElementById('evening-timer');
        let timeLeft = Math.max(0, secondsLeft | 0);

        // Сразу играет звук "закрытие_смены" (предупреждение)
        const soundWarning = document.getElementById('sound-shift-warning');
        if (soundWarning) {
            soundWarning.currentTime = 0;
            soundWarning.play();
        }

        if (typeof popupItem !== 'undefined') {
            popupItem.open('#popup-evening');
        }

        clearInterval(eveningCountdown);
        eveningCountdown = setInterval(() => {
            timeLeft--;
            if (eveningTimer) eveningTimer.textContent = formatTime(timeLeft);

            if (timeLeft <= 0) {
                clearInterval(eveningCountdown);

                // Играем финальный звук "смена закрыта"
                const soundClose = document.getElementById('sound-shift-close');
                if (soundClose) {
                    soundClose.currentTime = 0;
                    soundClose.play();
                }

                // Просто закрываем вечерний попап, ничего не меняя в тексте
                if (typeof popupItem !== 'undefined' && popupItem.close) {
                    popupItem.close('#popup-evening');
                }
            }
        }, 1000);
    }

    // Автостарт утреннего попапа отключён (Этап 4): запуск управляется по timers (Этап 5).
    // window.addEventListener('load', initMorningShift);


    // Функция смены лидеров
    function swapLeaders(id1, id2) {
        const container = document.getElementById('leaders-container');
        if (!container) return;

        const item1 = container.querySelector(`[data-id="${id1}"]`);
        const item2 = container.querySelector(`[data-id="${id2}"]`);

        if (!item1 || !item2) return;

        // Очищаем инлайновые стили от прошлых анимаций
        gsap.set([item1, item2], {clearProps: "all"});

        // 1. Определяем направление полета
        const rect1 = item1.getBoundingClientRect();
        const rect2 = item2.getBoundingClientRect();

        item1.classList.remove('leaders__item--climbing', 'leaders__item--falling');
        item2.classList.remove('leaders__item--climbing', 'leaders__item--falling');

        if (rect1.top > rect2.top) {
            item1.classList.add('leaders__item--climbing');
            item2.classList.add('leaders__item--falling');
        } else {
            item1.classList.add('leaders__item--falling');
            item2.classList.add('leaders__item--climbing');
        }

        // 2. ЗАПОМИНАЕМ СОСТОЯНИЕ ДО РОКИРОВКИ
        // Убираем border из отслеживания пропсов, чтобы Flip не ломал геометрию флексов в полете
        const state = Flip.getState([item1, item2], {props: "transform,opacity,backgroundColor"});

        // 3. МЕНЯЕМ МЕСТАМИ В DOM (цифры и рамки пока НЕ трогаем)
        const placeholder = document.createElement('div');
        item1.parentNode.insertBefore(placeholder, item1);
        item2.parentNode.insertBefore(item1, item2);
        placeholder.parentNode.insertBefore(item2, placeholder);
        placeholder.remove();

        // 4. ЗАПУСКАЕМ АНИМАЦИЮ ПОЛЕТА
        Flip.from(state, {
            duration: 0.8,
            ease: "power2.inOut",
            onComplete: () => {
                // 5. КОГДА КАРТОЧКИ ПОЛНОСТЬЮ ПРИЛЕТЕЛИ:
                const allItems = container.querySelectorAll('.leaders__item');
                allItems.forEach((item, index) => {
                    // Меняем циферку места
                    const positionNode = item.querySelector('.leaders__position');
                    if (positionNode) {
                        positionNode.textContent = index + 1;
                    }

                    // Обновляем призовые бордеры/классы
                    item.classList.remove('leaders__item--gold', 'leaders__item--silver', 'leaders__item--bronze');
                    if (index === 0) item.classList.add('leaders__item--gold');
                    if (index === 1) item.classList.add('leaders__item--silver');
                    if (index === 2) item.classList.add('leaders__item--bronze');
                });

                // Полностью убираем временные классы полета и сбрасываем костыли GSAP
                item1.classList.remove('leaders__item--climbing', 'leaders__item--falling');
                item2.classList.remove('leaders__item--climbing', 'leaders__item--falling');
                gsap.set([item1, item2], {clearProps: "all"});
            }
        });
    }

    // ========================================================
    // POLLING-ДВИЖОК: данные доски из JSON-эндпоинта /data
    // ========================================================
    const POLL_INTERVAL_MS = 30000;
    const IMG_BASE = (typeof window.RATING_BOARD_IMG !== 'undefined') ? window.RATING_BOARD_IMG : '/rating_board/img/';

    let lastData = null;      // последнее состояние (для diff быстроменящихся блоков)
    let audioUnlocked = false;

    /** Порог вечернего попапа: показать «до закрытия» за 30 минут. */
    const EVENING_THRESHOLD_SEC = 1800;
    /** Состояние попапов смен (чтобы не переоткрывать каждый polling). */
    const popupState = {morningShown: false, eveningShown: false};

    /** 100 фраз для первого места (рандомно выбираются и привязываются к user_id). */
    const GOLD_PHRASES = [
        "Победитель: Золотой наперсток.",
        "Лидер швейного мира.",
        "Королева нити и иголки.",
        "Мастер своего дела.",
        "Признанный гений шитья.",
        "Эталон качества и мастерства.",
        "Законодатель мод.",
        "Высшая лига портновского искусства.",
        "Вершина швейного ремесла.",
        "Чемпион по шитью.",
        "Лучший из лучших.",
        "Символ совершенства.",
        "Вдохновение для поколений.",
        "Звезда швейной индустрии.",
        "Титан портновского дела.",
        "Эксперт по тканям и стилю.",
        "Гуру швейной техники.",
        "Властелин швейной машины.",
        "Создатель шедевров.",
        "Непревзойденный талант.",
        "Профессионал высшей пробы.",
        "Человек-легенда в мире шитья.",
        "Обладатель престижной награды.",
        "Первый среди равных.",
        "Образец для подражания.",
        "Триумфатор конкурса.",
        "Лидер рейтинга.",
        "Швейный виртуоз.",
        "Маэстро кроя и шитья.",
        "Король портных.",
        "Истинный профессионал.",
        "Признанный мастер.",
        "Золотой стандарт качества.",
        "Эксперт мирового уровня.",
        "Человек, задающий тренды.",
        "Великий портной.",
        "Основоположник нового стиля.",
        "Чемпион швейных соревнований.",
        "Символ швейного мастерства.",
        "Лидер швейной отрасли.",
        "Признанный лидер.",
        "Звезда швейного мира.",
        "Наставник молодых талантов.",
        "Лучший в своем деле.",
        "Непревзойденный мастер.",
        "Абсолютный чемпион.",
        "Почетный победитель.",
        "Золотая игла.",
        "Мастер-портной.",
        "Эксперт в области шитья.",
        "Признанный лучший.",
        "Лидер швейного искусства.",
        "Создатель уникального стиля.",
        "Мастерство, не знающее границ.",
        "Образец профессионализма.",
        "Высшая степень мастерства.",
        "Чемпион швейного мастерства.",
        "Идол швейного мира.",
        "Символ победы.",
        "Основатель трендов.",
        "Величайший портной.",
        "Эксперт высшей категории.",
        "Лучший дизайнер.",
        "Мастер золотые руки.",
        "Признанный король шитья.",
        "Лидер индустрии моды.",
        "Золотой талант.",
        'Титул "Лучший швее года".',
        "Символ совершенства.",
        "Мастер-легенда.",
        "Эксперт мирового класса.",
        "Лучший среди профессионалов.",
        "Чемпион швейного искусства.",
        "Пример для всех.",
        "Золотой стандарт.",
        "Мастерство, покоряющее сердца.",
        "Лидер швейной элиты.",
        "Создатель неповторимого.",
        "Признанный авторитет.",
        "Лучший дизайнер одежды.",
        "Символ мастерства.",
        "Титан швейного мира.",
        "Эксперт от Бога.",
        "Чемпион швейных традиций.",
        "Звезда портновского дела.",
        "Мастер-виртуоз.",
        "Лучший швея планеты.",
        "Лидер мировых трендов.",
        "Признанный шедевр.",
        "Символ успеха.",
        "Золотой стандарт мастерства.",
        "Эксперт швейных технологий.",
        "Чемпион швейного ремесла.",
        "Мастер-виртуоз.",
        "Лучший из мира шитья.",
        "Триумфатор швейной индустрии."
    ];

    /** Текущий gold user_id и его фраза (сбрасываются при смене лидера). */
    let currentGoldUserId = null;
    let currentGoldText = null;

    /** Разблокировка звука после первого жеста пользователя (политика autoplay браузеров): тихий play+pause всех <audio>. */
    function unlockAudio() {
        if (audioUnlocked) return;
        audioUnlocked = true;
        document.querySelectorAll('audio').forEach(a => {
            a.muted = true;
            const p = a.play();
            if (p && typeof p.then === 'function') {
                p.then(() => {
                    a.pause();
                    a.currentTime = 0;
                    a.muted = false;
                }).catch(() => {
                    a.muted = false;
                });
            }
        });
    }

    document.addEventListener('click', unlockAudio);
    document.addEventListener('touchstart', unlockAudio);

    /** Безопасное экранирование текста для вставки в HTML. */
    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    /** Экранирование значения для HTML-атрибута. */
    function escapeAttr(value) {
        return escapeHtml(value).replace(/"/g, '&quot;');
    }

    /** HTML карточки лидера (таблица лидеров). */
    function buildLeaderHtml(leader) {
        const medal = leader.medal ? ` leaders__item--${leader.medal}` : '';
        return `<div data-id="${escapeAttr(leader.id)}" class="leaders__item${medal}">
            <div class="leaders__avatar"><img src="${escapeAttr(leader.avatar)}" alt="${escapeAttr(leader.name)}"></div>
            <div class="leaders__name">${escapeHtml(leader.name)}</div>
            <div class="leaders__position">${escapeHtml(leader.position)}</div>
        </div>`;
    }

    /** HTML элемента подиума для указанной медали (silver/gold/bronze). При отсутствии сотрудника — слот top__item--empty (крутится пустой круг). */
    function buildPodiumItemHtml(item, medal, position) {
        const baseCls = `top__item top__item--${medal}`;
        if (!item) {
            return `<div class="${baseCls} top__item--empty">
                <div class="top__star"><img src="${IMG_BASE}star-${medal}.webp" alt="вспышка"></div>
                <div class="top__avatar"></div>
                <div class="top__block"><div class="top__value"><span>${position}</span></div></div>
            </div>`;
        }
        // Для золота используем случайную фразу из 100 вариантов.
        // Если человек тот же → фраза сохраняется. Если новый или вернулся старый → новая фраза.
        let text = '';
        if (medal === 'gold' && item) {
            const userId = String(item.id);
            if (userId === currentGoldUserId && currentGoldText) {
                // Человек остался на 1 месте — сохраняем старую фразу
                text = `<div class="top__text">${escapeHtml(currentGoldText)}</div>`;
            } else {
                // Новый человек или вернувшийся старый — новая фраза
                currentGoldUserId = userId;
                currentGoldText = GOLD_PHRASES[Math.floor(Math.random() * GOLD_PHRASES.length)];
                text = `<div class="top__text">${escapeHtml(currentGoldText)}</div>`;
            }
        }

        return `<div data-id="${escapeAttr(item.id)}" class="${baseCls}">
            <div class="top__star"><img src="${IMG_BASE}star-${medal}.webp" alt="вспышка"></div>
            <div class="top__avatar"><img src="${escapeAttr(item.avatar)}" alt="${escapeAttr(item.name)}"></div>
            <div class="top__block">
                <div class="top__value"><span>${position}</span></div>
                ${text}
            </div>
        </div>`;
    }

    /** HTML карточки статистики. */
    function buildStatisticsCardHtml(card, medal) {
        const medalCls = medal ? ` statistics__item--${medal}` : '';
        return `<div class="statistics__item${medalCls}">
            <div class="statistics__avatar"><img src="${escapeAttr(card.avatar)}" alt="${escapeAttr(card.name)}"></div>
            <div class="statistics__content">
                <div class="statistics__name">${escapeHtml(card.name)}</div>
                <div class="statistics__profession">${escapeHtml(card.profession)}</div>
                <div class="statistics__value">${escapeHtml(card.value)}</div>
            </div>
            <div class="statistics__box">
                <div class="statistics__icon"></div>
                <div class="statistics__shift">${escapeHtml(card.shift)}</div>
            </div>
        </div>`;
    }

    /** Полная первичная отрисовка всех блоков из JSON (первый fetch). */
    function renderInitial(data) {
        renderLeaders(data.leaders || []);
        renderPodium(data.podium || {});
        renderShift(data.shift || {});
        renderWinner(data.winner || {});
        renderStatistics(data.statistics || []);
        renderStickers(data.stickers || {});
        initStatisticsScroll();
    }

    function renderLeaders(leaders) {
        const container = document.getElementById('leaders-container');
        if (container) container.innerHTML = leaders.map(buildLeaderHtml).join('');
    }

    function renderPodium(podium) {
        const container = document.getElementById('top-leaderboard');
        if (!container) return;
        // Порядок в DOM как в макете: silver (слева), gold (центр), bronze (справа).
        container.innerHTML =
            buildPodiumItemHtml(podium.silver, 'silver', 2) +
            buildPodiumItemHtml(podium.gold, 'gold', 1) +
            buildPodiumItemHtml(podium.bronze, 'bronze', 3);
    }

    function renderShift(shift) {
        const topTitle = document.querySelector('.top__title');
        if (topTitle) topTitle.textContent = shift.name ? ('СМЕНА ' + shift.name) : '';
        const winnerTitles = document.querySelectorAll('.winner__title');
        if (winnerTitles[0]) winnerTitles[0].textContent = shift.previous_name ? ('СМЕНА ' + shift.previous_name) : '';
    }

    function renderWinner(winner) {
        if (!winner) return;
        const nameEl = document.querySelector('.winner__name');
        if (nameEl) nameEl.textContent = winner.name || '';
        const avatarImg = document.querySelector('.winner__avatar img');
        if (avatarImg && winner.avatar) avatarImg.src = winner.avatar;
        const countP = document.querySelector('.winner__text p');
        if (countP && winner.orders_count != null) countP.textContent = `Выполнено ${winner.orders_count} заказов!`;
    }

    function renderStatistics(statistics) {
        const container = document.querySelector('.statistics__items');
        if (!container) return;
        container.innerHTML = statistics.map((card) => buildStatisticsCardHtml(card, card.medal || null)).join('');
    }

    function renderStickers(stickers) {
        setStickerValue('sticker-fbo', stickers.fbo);
        setStickerValue('sticker-fbs', stickers.fbs);
    }

    function setStickerValue(id, value) {
        const sticker = document.getElementById(id);
        if (!sticker) return;
        const span = sticker.querySelector('span');
        if (span) span.textContent = value;
    }

    /** Diff лидеров: тот же состав — серия соседних свапов (bubble), иначе — перестройка. */
    function applyLeadersDiff(prev, next) {
        const prevIds = prev.map(l => String(l.id));
        const nextIds = next.map(l => String(l.id));
        if (prevIds.join(',') === nextIds.join(',')) return;

        const sameSet = prevIds.length === nextIds.length && nextIds.every(id => prevIds.includes(id));
        if (!sameSet) {
            renderLeaders(next);
            return;
        }

        const cur = prevIds.slice();
        const swaps = [];
        for (let i = 0; i < nextIds.length; i++) {
            if (cur[i] !== nextIds[i]) {
                let j = cur.indexOf(nextIds[i], i);
                for (let k = j; k > i; k--) {
                    swaps.push([cur[k - 1], cur[k]]);
                    [cur[k - 1], cur[k]] = [cur[k], cur[k - 1]];
                }
            }
        }
        runSwapsSequentially(swaps);
    }

    /** Выполняет свапы лидеров по цепочке с паузой на анимацию Flip (~0.8с). */
    function runSwapsSequentially(swaps) {
        if (!swaps.length) return;
        const [a, b] = swaps.shift();
        swapLeaders(a, b);
        setTimeout(() => runSwapsSequentially(swaps), 900);
    }

    /** Синхронизирует элементы подиума (data-id/аватар/класс --empty) с актуальным state. */
    function syncPodiumDataIds(podium) {
        const map = {
            gold: podium.gold,
            silver: podium.silver,
            bronze: podium.bronze
        };
        Object.keys(map).forEach(medal => {
            const el = leaderboard.querySelector(`.top__item--${medal}`);
            if (!el) return;
            const data = map[medal];
            const avatar = el.querySelector('.top__avatar');
            if (data) {
                el.classList.remove('top__item--empty');
                el.setAttribute('data-id', data.id);
                if (avatar) avatar.innerHTML = `<img src="${escapeAttr(data.avatar)}" alt="${escapeAttr(data.name)}">`;

                // Обновляем текст для золота (логика как в buildPodiumItemHtml)
                if (medal === 'gold') {
                    const userId = String(data.id);
                    if (userId === currentGoldUserId && currentGoldText) {
                        // Человек остался на 1 месте
                        const textBlock = el.querySelector('.top__text');
                        if (textBlock) textBlock.textContent = currentGoldText;
                    } else {
                        // Новый человек или вернувшийся старый — новая фраза
                        currentGoldUserId = userId;
                        currentGoldText = GOLD_PHRASES[Math.floor(Math.random() * GOLD_PHRASES.length)];
                        const textBlock = el.querySelector('.top__text');
                        if (textBlock) textBlock.textContent = currentGoldText;
                    }
                }
            } else {
                el.classList.add('top__item--empty');
                el.removeAttribute('data-id');
                if (avatar) avatar.innerHTML = '';
            }
        });
    }

    /** Diff подиума: точечные свапы для типичных ротаций, иначе — перестройка. */
    function applyPodiumDiff(prev, next) {
        // Если подиум пустой (нет лидеров) — ничего не делаем
        if (!next.gold && !next.silver && !next.bronze) return;

        const g0 = prev.gold?.id, s0 = prev.silver?.id, b0 = prev.bronze?.id;
        const g1 = next.gold?.id, s1 = next.silver?.id, b1 = next.bronze?.id;

        // Золото ⇄ Серебро
        if (g1 === s0 && s1 === g0 && b1 === b0 && g1 && s1) {
            swapGoldSilver();
            setTimeout(() => syncPodiumDataIds(next), 650);
            return;
        }
        // Серебро ⇄ Бронза
        if (s1 === b0 && b1 === s0 && g1 === g0 && s1 && b1) {
            swapSilverBronze();
            setTimeout(() => syncPodiumDataIds(next), 650);
            return;
        }
        // Новая бронза (золото/серебро на месте)
        if (g1 === g0 && s1 === s0 && b1 !== b0 && next.bronze) {
            replaceBronzePlace(next.bronze.avatar, next.bronze.id);
            setTimeout(() => syncPodiumDataIds(next), 850);
            return;
        }
        // Иная ротация — перестройка без анимации.
        renderPodium(next);
    }

    /** Diff стикеров: плавная смена числа со звуком при изменении. */
    function applyStickersDiff(prev, next) {
        if (prev.fbo !== next.fbo) animateStickerValue('sticker-fbo', next.fbo);
        if (prev.fbs !== next.fbs) animateStickerValue('sticker-fbs', next.fbs);
    }

    /** Применяет diff к быстроменяющимся блокам. Медленные (shift/winner/statistics) обновятся при reload страницы. */
    function applyPollDiff(prev, next) {
        applyLeadersDiff(prev.leaders || [], next.leaders || []);
        applyPodiumDiff(prev.podium || {}, next.podium || {});
        applyStickersDiff(prev.stickers || {
            fbo: 0,
            fbs: 0
        }, next.stickers || {fbo: 0, fbs: 0});
    }

    /** Запрашивает JSON данных доски. */
    async function fetchData() {
        const token = document.body.dataset.token;
        const workshop = document.body.dataset.workshop;
        const resp = await fetch(`/rating_board/${encodeURIComponent(token)}/${encodeURIComponent(workshop)}/data`, {
            headers: {Accept: 'application/json'},
        });
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        return resp.json();
    }

    /** Один цикл polling: fetch → первичная отрисовка или diff. */
    /** Управляет попапами смен по timers из JSON: утренний (до открытия) и вечерний (ближе к закрытию). */
    function applyTimers(timers) {
        if (!timers) return;
        const morning = timers.morning_seconds_left || 0;
        const evening = timers.evening_seconds_left || 0;

        if (morning > 0) {
            if (!popupState.morningShown) {
                popupState.morningShown = true;
                initMorningShift(morning);
            }
        } else {
            popupState.morningShown = false;
        }

        if (evening > 0 && evening <= EVENING_THRESHOLD_SEC) {
            if (!popupState.eveningShown) {
                popupState.eveningShown = true;
                triggerEveningWarning(evening);
            }
        } else {
            popupState.eveningShown = false;
        }
    }

    async function poll() {
        try {
            const data = await fetchData();
            applyTimers(data.timers);
            if (!lastData) {
                renderInitial(data);
            } else {
                applyPollDiff(lastData, data);
            }
            lastData = data;
        } catch (e) {
            console.warn('rating_board poll error', e);
        }
    }

    // Первый опрос — сразу, далее по интервалу.
    poll();
    setInterval(poll, POLL_INTERVAL_MS);

});
