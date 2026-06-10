# TransactionController

**Путь:** `app/Http/Controllers/TransactionController.php`

**Описание:** Контроллер для управления финансовыми транзакциями, зарплатами и
бонусами

**Зависимости:**

- `App\Http\Requests\CreateTransactionRequest` - валидация транзакций
- `App\Models\Transaction` - модель транзакции
- `App\Models\User` - модель пользователя
- `App\Services\TransactionService` - сервис транзакций
- `Illuminate\Http\Request` - HTTP запросы

---

## Методы контроллера

### index(Request $request)

- **Описание:** Отображение финансовых операций
- **Параметры:** `$request` - HTTP запрос с фильтрами
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Финансы" или "Финансы компании" для админа
    - `request` (Request) - объект запроса для фильтров
    - `users` (Collection) - все пользователи
    - `totalInCompany` (float) - общие деньги в компании
    - `total` (float) - общие транзакции
    - `total_bonus` (float) - общие бонусы
    - `cashflow` (LengthAwarePaginator) - пагинированный денежный поток (5 на
      страницу)
    - `transactions` (LengthAwarePaginator) - пагинированные транзакции (5 на
      страницу)
- **View:** `transactions.index`
- **Использует:** `TransactionService` для расчетов и фильтрации

### create($type)

- **Описание:** Форма создания новой финансовой операции
- **Параметры:** `$type` (string) - тип операции (salary/bonus/company)
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `type` (string) - тип операции
    - `title` (string) - заголовок с типом операции
    - `users` (Collection) - все пользователи
- **View:** `transactions.create`
- **Типы операций:**
    - `salary` - операции с зарплатой
    - `bonus` - операции с бонусами
    - `company` - операции компании

### store(CreateTransactionRequest $request)

- **Описание:** Сохранение новой транзакции
- **Параметры:** `$request` - валидированные данные транзакции
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Использует:** `TransactionService::store()`
- **Логика:**
    - Вызывает сервис для сохранения
    - Проверяет результат (достаточно ли денег в кассе)
- **Редирект:** `route('transactions.index')`

### destroy(Transaction $transaction)

- **Описание:** Удаление транзакции
- **Параметры:** `$transaction` - модель транзакции
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Проверка:** Нельзя удалить выплаченную транзакцию (`paid_at` не null)
- **Логика:** Простое удаление модели

### createPayoutSalary(Request $request)

- **Описание:** Страница выплаты зарплаты
- **Параметры:** `$request` - HTTP запрос
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Выплата"
    - `users` (Collection) - все пользователи
    - `selected_user` (User|null) - выбранный пользователь
    - `payouts` (Collection) - последние выплаты (10)
    - `request` (Request) - объект запроса
    - `net_payout` (float) - сумма к выплате
    - `oldestUnpaidSalaryDate` (date) - старейшая невыплаченная зарплата
    - `moneyInCompany` (float) - деньги в компании
- **View:** `transactions.payout`
- **Использует:** `TransactionService` для расчетов

### storePayoutSalary(Request $request)

- **Описание:** Выполнение выплаты зарплаты
- **Параметры:** `$request` - HTTP запрос с датами
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Логика:**
    1. Ищет транзакции за период
    2. Рассчитывает разницу между доходами и расходами
    3. Проверяет наличие денег в компании
    4. Обновляет статус транзакций на "выплачено"
- **Проверка:** Недостаточно денег для выплаты - отмена операции

### createPayoutBonus(Request $request)

- **Описание:** Страница выплаты бонусов
- **Параметры:** `$request` - HTTP запрос
- **Возвращает:** `Illuminate\View\View`
- **Данные:**
    - `title` (string) - "Выплата"
    - `users` (Collection) - все пользователи
    - `selected_user` (User|null) - выбранный пользователь
    - `payouts` (Collection) - последние выплаты бонусов
    - `hold_bonus` (Collection) - замороженные бонусы
    - `allHoldBonus` (float) - сумма замороженных бонусов к выплате
    - `request` (Request) - объект запроса
- **View:** `transactions.payout_bonus`

### storePayoutBonus(Request $request)

- **Описание:** Выполнение выплаты бонусов
- **Параметры:** `$request` - HTTP запрос
- **Возвращает:** `Illuminate\Http\RedirectResponse`
- **Логика:**
    1. Ищет невыплоченные бонусы со статусом 1
    2. Обновляет их статус на "выплачено"
    3. Устанавливает время выплаты
- **Проверка:** Отсутствие бонусов к выплате - ошибка

---

## Особенности реализации

1. **Service Layer:** Основная бизнес-логика вынесена в `TransactionService`
2. **Финансовые проверки:** Контроль баланса кассы при операциях
3. **Пагинация:** Раздельная пагинация для транзакций и денежного потока
4. **Статусы транзакций:** Используются статусы для отслеживания выплат
5. **Валидация:** Использование Form Request для безопасности данных
6. **Route Model Binding:** Автоматическая подгрузка моделей

---

## Статусы транзакций

- 0 - Новая/невыплаченная
- 1 - В обработке/заморожена
- 2 - Выплачена

---

## Роуты

- `GET /transactions` - `index` - список транзакций
- `GET /transactions/create/{type}` - `create` - форма создания
- `POST /transactions` - `store` - сохранение
- `DELETE /transactions/{transaction}` - `destroy` - удаление
- `GET /transactions/create_payout_salary` - `createPayoutSalary` - форма
  выплаты зарплаты
- `POST /transactions/store_payout_salary` - `storePayoutSalary` - выплата
  зарплаты
- `GET /transactions/create_payout_bonus` - `createPayoutBonus` - форма выплаты
  бонусов
- `POST /transactions/store_payout_bonus` - `storePayoutBonus` - выплата бонусов

---

## Сервисные зависимости

### TransactionService

- `getTotalByType($request, $isBonus, $isCompany)` - расчет сумм
- `getCashflowFiltered($request)` - фильтрация денежного потока
- `getFiltered($request)` - фильтрация транзакций
- `store($request)` - сохранение транзакции
- `getLastPayouts($user, $count, $isBonus)` - последние выплаты
- `getSumOfPayout($request)` - сумма к выплате
- `getOldestUnpaidSalaryEntry($user)` - старейшая невыплаченная зарплата
- `getHoldBonus($user)` - замороженные бонусы

---

## Права доступа

- Просмотр транзакций: авторизованные пользователи
- Создание транзакций: ограниченный доступ (обычно администраторы)
- Удаление транзакций: только невыплаченные
- Выплата зарплат/бонусов: административные права
