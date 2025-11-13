<?php

namespace App\Services;

use App\Models\Stack;

class StackService
{
    private static function getMaxStackByUser($seamstressId)
    {
        return Stack::query()->firstOrCreate(
            ['seamstress_id' => $seamstressId],
            ['seamstress_id' => $seamstressId, 'stack' => '0', 'max' => '0']
        );
    }

    public static function incrementStackAndMaxStack($seamstressId): void
    {
        $stack = Stack::query()->where('seamstress_id', $seamstressId)->first();

        $stack->stack = $stack->stack + 1;
        $stack->max = $stack->max + 1;
        $stack->save();
    }

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

    private static function resetMaxStackToZero(mixed $seamstressId): void
    {
        $stack = Stack::query()->where('seamstress_id', $seamstressId)->first();

        $stack->max = 0;
        $stack->save();
    }
}
