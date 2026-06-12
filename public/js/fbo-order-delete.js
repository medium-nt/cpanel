/**
 * AJAX удаление заказов в FBO поставках без перезагрузки страницы
 *
 * Используется в show-ozon-fbo.blade.php и show-wb-fbo.blade.php
 */
document.addEventListener('DOMContentLoaded', function () {
    // AJAX удаление одного заказа
    document.querySelectorAll('.delete-order-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const orderId = this.querySelector('button').getAttribute('data-order-id');

            if (!confirm(`Удалить заказ ${orderId} из системы безвозвратно вместе с товарами?`)) {
                return;
            }

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.querySelector('input[name="_token"]').value,
                    'X-HTTP-Method-Override': 'DELETE',
                    'Accept': 'application/json'
                },
                body: new FormData(this)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Удаляем все строки этого заказа
                        const rows = document.querySelectorAll(`tr[data-order-id]`);
                        const orderIdAttr = this.action.match(/\/delete\/(\d+)/)?.[1];

                        rows.forEach(row => {
                            if (row.getAttribute('data-order-id') === orderIdAttr) {
                                row.remove();
                            }
                        });

                        // Обновляем счётчик
                        const countEl = document.querySelector('.orders-count');
                        const currentCount = parseInt(countEl.textContent.match(/\d+/)[0]);
                        countEl.textContent = `(${currentCount - 1})`;

                        toastr.success(data.message || 'Заказ удален');
                    } else {
                        toastr.error(data.message || 'Ошибка удаления');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Ошибка при удалении заказа');
                });
        });
    });

    // AJAX массовое удаление новых заказов
    document.querySelectorAll('.delete-all-new-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!confirm('Удалить ВСЕ заказы в статусе «новый» из этой поставки? Они будут удалены безвозвратно из системы вместе с товарами.')) {
                return;
            }

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.querySelector('input[name="_token"]').value,
                    'X-HTTP-Method-Override': 'DELETE',
                    'Accept': 'application/json'
                },
                body: new FormData(this)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Удаляем все строки новых заказов (status == 0)
                        const table = document.querySelector('table tbody');
                        const rows = table.querySelectorAll('tr');
                        const ordersToDelete = new Set();

                        // Собираем ID заказов для удаления
                        rows.forEach(row => {
                            const statusBadge = row.querySelector('.badge');
                            if (statusBadge && statusBadge.textContent.trim() === 'Новый') {
                                const orderId = row.getAttribute('data-order-id');
                                if (orderId) {
                                    ordersToDelete.add(orderId);
                                }
                            }
                        });

                        // Удаляем строки
                        rows.forEach(row => {
                            if (ordersToDelete.has(row.getAttribute('data-order-id'))) {
                                row.remove();
                            }
                        });

                        // Обновляем счётчик
                        const countEl = document.querySelector('.orders-count');
                        const remainingCount = rows.length - ordersToDelete.size;
                        countEl.textContent = `(${remainingCount})`;

                        // Скрываем кнопку "Удалить все новые"
                        form.style.display = 'none';

                        toastr.success(data.message || 'Заказы удалены');
                    } else {
                        toastr.error(data.message || 'Ошибка удаления');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('Ошибка при удалении заказов');
                });
        });
    });
});
