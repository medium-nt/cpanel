// Хранилище напечатанных orderId в текущей сессии
const printedOrders = new Set();

let confirmResolve = null;
let confirmModal = null;

function showConfirmModal() {
    return new Promise(resolve => {
        confirmResolve = resolve;

        // Проверка существования modal (для страниц без confirmReprintModal)
        const modalElement = document.getElementById('confirmReprintModal');
        if (!modalElement) {
            resolve(true);
            return;
        }

        if (!confirmModal) {
            confirmModal = new bootstrap.Modal(modalElement);
        }
        confirmModal.show();
    });
}

function printBarcode(url, isPrinted = false, btnElement = null) {
    const urlObj = new URL(url, window.location.origin);
    const orderId = urlObj.searchParams.get('marketplaceOrderId');

    const alreadyPrinted = isPrinted || printedOrders.has(orderId);
    if (alreadyPrinted) {
        showConfirmModal().then(confirmed => {
            if (confirmed) executePrint(url, orderId, btnElement);
        });
        return;
    }

    executePrint(url, orderId, btnElement);
}

// Кнопка "Продолжить"
const confirmBtn = document.getElementById('confirmReprintBtn');
if (confirmBtn) {
    confirmBtn.onclick = () => {
        confirmModal?.hide();
        confirmResolve?.(true);
    };
}

// Сброс при закрытии модалки (кнопка "Отмена" или backdrop)
const confirmModalEl = document.getElementById('confirmReprintModal');
if (confirmModalEl) {
    confirmModalEl.addEventListener('hidden.bs.modal', () => {
        confirmResolve?.(false);
        confirmResolve = null;
    });
}

function resetPrintButton(printBtn, printSpinner, printBtnText) {
    if (printBtn) printBtn.disabled = false;
    if (printSpinner) {
        printSpinner.classList.add('d-none');
        const icon = printBtn.querySelector('.barcode-icon');
        if (icon) icon.classList.remove('d-none');
    }
    if (printBtnText) printBtnText.textContent = 'Распечатать 20 стикеров';
}

async function executePrint(url, orderId, btnElement = null) {
    const iframe = document.getElementById('printFrame');
    url = url.startsWith('/') ? window.location.origin + url : url;

    // Определяем элементы для индикации загрузки
    let printBtn, printSpinner, printBtnText;

    if (btnElement) {
        // Если передана кнопка (печать из таблицы defects)
        printBtn = btnElement;
        printSpinner = btnElement.querySelector('.print-spinner');
    } else {
        // Иначе ищем по id (стандартная кнопка)
        printBtn = document.getElementById('printStickerBtn');
        printSpinner = document.getElementById('printSpinner');
        printBtnText = document.getElementById('printBtnText');
    }

    if (printBtn) {
        printBtn.disabled = true;
    }
    if (printSpinner) {
        printSpinner.classList.remove('d-none');
        // Скрываем иконку штрихкода
        const icon = printBtn.querySelector('.barcode-icon');
        if (icon) icon.classList.add('d-none');
    }
    if (printBtnText) {
        printBtnText.textContent = 'Печать...';
    }

    try {
        const response = await fetch(url);
        const blob = await response.blob();
        const blobUrl = URL.createObjectURL(blob);

        iframe.onload = () => {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                printedOrders.add(orderId);
            } catch (e) {
                console.error(e);
            }
            URL.revokeObjectURL(blobUrl);
            resetPrintButton(printBtn, printSpinner, printBtnText);
        };

        iframe.src = blobUrl;
    } catch (e) {
        console.error('Error loading PDF:', e);
        resetPrintButton(printBtn, printSpinner, printBtnText);
    }
}
