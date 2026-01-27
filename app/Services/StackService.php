<?php

namespace App\Services;

use App\Models\Stack;
use Illuminate\Support\Facades\Log;

class StackService
{
    /**
     * Получаем максимальное значение стэка у сотрудника.
     */
    public static function getMaxStackByUser($seamstressId): Stack
    {
        return Stack::query()->firstOrCreate(
            ['seamstress_id' => $seamstressId],
            ['seamstress_id' => $seamstressId, 'stack' => '0', 'max' => '0']
        );
    }

    /**
     * Увеличиваем стек на 1.
     */
    public static function incrementStackAndMaxStack($seamstressId): void
    {
        $stack = Stack::query()->where('seamstress_id', $seamstressId)->first();

        $stack->stack = $stack->stack + 1;
        $stack->max = $stack->max + 1;
        $stack->save();
    }

    /**
     * Уменьшаем стек на 1 и если это последний заказ в стэке, то обнуляем стэк.
     */
    public static function reduceStack($seamstressId): void
    {
        $stack = Stack::query()->where('seamstress_id', $seamstressId)->first();

        $stack->stack = $stack->stack - 1;
        $stack->save();

        if ($stack->stack == 0) {
            $maxStack = self::getMaxStackByUser($seamstressId)->max;
            $maxCountOrderItems = MarketplaceOrderItemService::getMaxQuantityOrdersToUserRole();

            if ($maxStack >= $maxCountOrderItems) {
                self::resetMaxStackToZero($seamstressId);
            }
        }
    }

    /**
     * Обнуляем стэк.
     */
    private static function resetMaxStackToZero(mixed $seamstressId): void
    {
        $stack = Stack::query()->where('seamstress_id', $seamstressId)->first();

        $stack->max = 0;
        $stack->save();
    }

    /**
     * Очищает стеки у всех сотрудников.
     * Обнуляет колонки stack и max в таблице stacks.
     */
    public static function clearAllStacks(): void
    {
        Stack::query()->update([
            'stack' => 0,
            'max' => 0,
        ]);

        Log::channel('work_shift')
            ->info('Выполнена очистка стеков у всех сотрудников.');
    }
}
