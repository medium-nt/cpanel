## [2026-06-24] update | fbo-order-detachment-third-button

- Добавлена ТРЕТЬЯ кнопка "Убрать на поставку" для массовой отвязки заказов
  в статусе 6 ("На поставку") от FBO-поставок: отвязка supply_id=null
  (без удаления), только заказы без короба (box_id IS NULL)
- `MarketplaceOrderService::detachOnSupplyOrdersBySupply()` — новый метод
- `MarketplaceOrderController::detachOnSupplyBySupply()` — обработчик
- Новый роут `DELETE /megatulle/marketplace_orders/detach-on-supply-by-supply/`
- Кнопка голубая (btn-info), доступна только admin при `$supply->status === 13`
- **Всего три массовых действия на странице FBO-поставки:**
    1. "Удалить все новые" (status=0) — полное удаление
    2. "Убрать не готовые" (status=4) — отвязка заказов без короба
    3. "Убрать на поставку" (status=6) — отвязка заказов без короба ✨ **НОВОЕ**
- Логирование в канал `orders` при отвязке
- Обновлены topics: order-lifecycle.md, marketplace-integration.md, finance.md
