## [2026-06-20] update | materials-workshop-restriction

- Добавлена система ограничения материалов по цехам: pivot-таблица
  `material_workshop` связывает Material и Workshop (многие-ко-многим)
- `Workshop::allowedMaterials()` — материалы, доступные для заказа в цехе
- `Material::workshops()` — обратная связь
- `AutoOrderService` теперь фильтрует материалы по цеху (только привязанные
  через material_workshop)
- `MovementMaterialToWorkshopController::create()` — dropdown материалов
  фильтруется по цеху пользователя
- `MovementMaterialToWorkshopService::store()` — добавлена проверка доступности
  материала цеху
- `WorkshopController::edit/update` — UI управления привязкой материалов к
  цеху (чекбоксы в `resources/views/workshops/edit.blade.php`)
- Обновлены topics: material-flow.md, shift-system.md, warehouse-operations.md
