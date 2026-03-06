/**
 * Item Card - JavaScript functionality for kiosk item card operations
 * Handles repack, replace, and defect workflows
 */

(function () {
    'use strict';

    // Получаем данные из data-атрибутов body
    const body = document.body;
    const action = body.dataset.action;
    const csrfToken = body.dataset.csrfToken;

    // Данные для подмены (replace)
    const replaceData = {
        storageUrl: body.dataset.replaceStorageUrl || '',
        processUrl: body.dataset.replaceProcessUrl || ''
    };

    // Данные для переупаковки (repack)
    const repackData = {
        itemId: body.dataset.repackItemId || '',
        storageUrl: body.dataset.repackStorageUrl || ''
    };

    // Состояние для подмены
    let replaceItemCreated = false;
    let replacePrintClicked = false;
    let newMarketplaceOrderId = null;
    let newItemId = null;

    // Состояние для переупаковки
    let repackPrintClicked = false;

    // === Utility Functions ===

    function showAlert(type, title, message) {
        const alertEl = document.getElementById('alert');
        const alertTitle = document.getElementById('alert-title');
        const alertMessage = document.getElementById('alert-message');

        if (alertEl && alertTitle && alertMessage) {
            alertEl.className = 'alert alert-' + type;
            alertTitle.textContent = title;
            alertMessage.textContent = message;
        }
    }

    function playSound(id) {
        const audio = document.getElementById(id);
        if (audio) {
            audio.currentTime = 0;
            audio.play().catch(() => {
            });
        }
    }

    // === Replace (Подмена) Functions ===

    function createReplaceItem() {
        const material = document.getElementById('replace-material').value;
        const width = document.getElementById('replace-width').value;
        const height = document.getElementById('replace-height').value;
        const materialUsed = document.getElementById('replace-material-used').value;

        fetch(replaceData.processUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                material_title: material,
                width: parseInt(width),
                height: parseInt(height),
                material_used: materialUsed
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    newMarketplaceOrderId = data.marketplace_order_id;
                    newItemId = data.item_id;
                    replaceItemCreated = true;

                    lockReplaceSelects();

                    document.getElementById('replace-create-btn').classList.add('d-none');
                    const printButtons = document.getElementById('replace-print-buttons');
                    printButtons.classList.remove('d-none');
                    printButtons.classList.add('d-flex');

                    showAlert('success', 'Успех', 'Новый товар создан');
                    playSound('scan-success-sound');
                } else {
                    showAlert('danger', 'Ошибка', data.message || 'Ошибка создания товара');
                    playSound('scan-error-sound');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Ошибка', 'Ошибка сети');
                playSound('scan-error-sound');
            });
    }

    function lockReplaceSelects() {
        document.getElementById('replace-material').disabled = true;
        document.getElementById('replace-width').disabled = true;
        document.getElementById('replace-height').disabled = true;
        document.getElementById('replace-material-used').disabled = true;
    }

    function printReplaceSticker() {
        const btn = document.getElementById('replace-print-storage-btn');
        const url = replaceData.storageUrl + '?marketplace_items=' + newItemId;

        printBarcode(url, false, btn);
        markReplacePrintClicked();
    }

    function markReplacePrintClicked() {
        replacePrintClicked = true;
        setTimeout(() => {
            document.getElementById('replace-complete-btn').classList.remove('d-none');
        }, 3000);
    }

    function checkReplaceFields() {
        const replaceMaterial = document.getElementById('replace-material');
        const replaceWidth = document.getElementById('replace-width');
        const replaceHeight = document.getElementById('replace-height');
        const replaceMaterialUsed = document.getElementById('replace-material-used');

        if (replaceMaterial && replaceWidth && replaceHeight && replaceMaterialUsed) {
            const materialSelected = replaceMaterial.value !== '';
            const widthSelected = replaceWidth.value !== '';
            const heightSelected = replaceHeight.value !== '';
            const materialUsedSelected = replaceMaterialUsed.value !== '';

            const allSelected = materialSelected && widthSelected && heightSelected && materialUsedSelected;

            if (allSelected && !replaceItemCreated) {
                document.getElementById('replace-create-btn').classList.remove('d-none');
            } else {
                document.getElementById('replace-create-btn').classList.add('d-none');
            }
        }
    }

    // === Repack (Переупаковка) Functions ===

    function printRepackSticker() {
        const btn = document.getElementById('repack-print-storage-btn');
        const url = repackData.storageUrl + '?marketplace_items=' + repackData.itemId;

        printBarcode(url, false, btn);
        markRepackPrintClicked();
    }

    function markRepackPrintClicked() {
        repackPrintClicked = true;
        setTimeout(() => checkRepackFields(), 3000);
    }

    function checkRepackFields() {
        const materialUsed = document.getElementById('material-used');
        const repackPrintStorageBtn = document.getElementById('repack-print-storage-btn');
        const repackCompleteBtn = document.getElementById('repack-complete-btn');

        if (materialUsed) {
            const materialSelected = materialUsed.value !== '';

            if (materialSelected) {
                if (repackPrintStorageBtn) repackPrintStorageBtn.classList.remove('d-none');
            } else {
                if (repackPrintStorageBtn) repackPrintStorageBtn.classList.add('d-none');
                repackCompleteBtn.classList.add('d-none');
            }

            if (materialSelected && repackPrintClicked) {
                repackCompleteBtn.classList.remove('d-none');
            } else {
                repackCompleteBtn.classList.add('d-none');
            }
        }
    }

    // === Defect (Брак) Functions ===

    function checkDefectField() {
        const defectReason = document.getElementById('defect-reason');
        const defectCompleteBtn = document.getElementById('defect-complete-btn');

        if (defectReason && defectCompleteBtn) {
            if (defectReason.value !== '') {
                defectCompleteBtn.classList.remove('d-none');
            } else {
                defectCompleteBtn.classList.add('d-none');
            }
        }
    }

    // === Initialization ===

    function init() {
        // Replace event listeners
        const replaceMaterial = document.getElementById('replace-material');
        const replaceWidth = document.getElementById('replace-width');
        const replaceHeight = document.getElementById('replace-height');
        const replaceMaterialUsed = document.getElementById('replace-material-used');

        if (replaceMaterial) replaceMaterial.addEventListener('change', checkReplaceFields);
        if (replaceWidth) replaceWidth.addEventListener('change', checkReplaceFields);
        if (replaceHeight) replaceHeight.addEventListener('change', checkReplaceFields);
        if (replaceMaterialUsed) replaceMaterialUsed.addEventListener('change', checkReplaceFields);

        // Repack event listeners
        const materialUsed = document.getElementById('material-used');
        if (materialUsed) {
            materialUsed.addEventListener('change', function () {
                repackPrintClicked = false;
                checkRepackFields();
            });
        }

        // Defect event listeners
        const defectReason = document.getElementById('defect-reason');
        if (defectReason) {
            defectReason.addEventListener('change', checkDefectField);

            // Восстанавливаем выбранную причину после ошибки валидации
            if (body.dataset.defectOldReason) {
                defectReason.value = body.dataset.defectOldReason;
                checkDefectField();
            }
        }

        // Делаем функции глобальными для onclick атрибутов
        window.createReplaceItem = createReplaceItem;
        window.printReplaceSticker = printReplaceSticker;
        window.printRepackSticker = printRepackSticker;
    }

    // Запускаем инициализацию после загрузки DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
