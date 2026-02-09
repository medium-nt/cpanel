// Хранилище напечатанных orderId в текущей сессии
const printedOrders = new Set();

let confirmResolve = null;
let confirmModal = null;

function showConfirmModal() {
    return new Promise(resolve => {
        confirmResolve = resolve;
        if (!confirmModal) {
            confirmModal = new bootstrap.Modal(document.getElementById('confirmReprintModal'));
        }
        confirmModal.show();
    });
}

function printBarcode(url, isPrinted = false, force = false) {
    const urlObj = new URL(url, window.location.origin);
    const orderId = urlObj.searchParams.get('marketplaceOrderId');

    if (force) {
        executePrint(url, orderId);
        return;
    }

    const alreadyPrinted = isPrinted || printedOrders.has(orderId);
    if (alreadyPrinted) {
        showConfirmModal().then(confirmed => {
            if (confirmed) executePrint(url, orderId);
        });
        return;
    }

    executePrint(url, orderId);
}

// Кнопка "Продолжить"
document.getElementById('confirmReprintBtn').onclick = () => {
    confirmModal?.hide();
    confirmResolve?.(true);
};

// Сброс при закрытии модалки (кнопка "Отмена" или backdrop)
document.getElementById('confirmReprintModal').addEventListener('hidden.bs.modal', () => {
    confirmResolve?.(false);
    confirmResolve = null;
});

async function executePrint(url, orderId) {
    const iframe = document.getElementById('printFrame');
    url = url.startsWith('/') ? window.location.origin + url : url;

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
        };

        iframe.src = blobUrl;
    } catch (e) {
        console.error('Error loading PDF:', e);
    }
}
