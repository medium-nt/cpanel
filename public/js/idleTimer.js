export default class IdleTimer {
    constructor(options = {}) {
        this.redirectUrl = options.redirectUrl;
        this.modalSelector = options.modalSelector || '#idleModal';

        this.idleTimeout = 60000;
        this.countdownSeconds = 60;

        this.modal = document.querySelector(this.modalSelector);
        this.countdownEl = document.querySelector(`${this.modalSelector} #idleCountdown`);
        this.stayBtn = document.querySelector(`${this.modalSelector} #stayBtn`);

        this.idleTimer = null;
        this.countdownTimer = null;
        this.countdownValue = this.countdownSeconds;

        if (!this.modal) {
            console.error('IdleTimer: Modal not found');
            return;
        }

        this.init();
    }

    init() {
        this.stayBtn.addEventListener('click', () => this.reset());

        ['mousemove', 'keypress', 'click', 'touchstart'].forEach(event => {
            document.addEventListener(event, () => this.reset());
        });

        this.start();
    }

    start() {
        this.idleTimer = setTimeout(() => this.showModal(), this.idleTimeout);
    }

    stop() {
        clearTimeout(this.idleTimer);
        clearInterval(this.countdownTimer);
    }

    reset() {
        this.stop();
        this.hideModal();
        this.start();
    }

    showModal() {
        this.countdownValue = this.countdownSeconds;
        this.updateCountdown();

        this.modal.classList.add('show');
        this.modal.style.display = 'block';
        document.body.classList.add('modal-open');

        this.showBackdrop();

        this.countdownTimer = setInterval(() => {
            this.countdownValue--;
            this.updateCountdown();

            if (this.countdownValue <= 0) {
                this.stop();
                window.location.href = this.redirectUrl;
            }
        }, 1000);
    }

    hideModal() {
        this.modal.classList.remove('show');
        this.modal.style.display = 'none';
        document.body.classList.remove('modal-open');

        this.hideBackdrop();
    }

    showBackdrop() {
        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    }

    hideBackdrop() {
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }

    updateCountdown() {
        if (this.countdownEl) {
            this.countdownEl.textContent = this.countdownValue;
        }
    }
}
