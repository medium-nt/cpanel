<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperMarketplace
 */
class Marketplace extends Model
{
    /** Идентификатор маркетплейса Ozon в таблице marketplaces. */
    public const OZON = 1;

    /** Идентификатор маркетплейса Wildberries в таблице marketplaces. */
    public const WB = 2;

    /** Путь к логотипу маркетплейса (OZON/WB) по его ID. */
    const LOGO = [
        self::OZON => '/icons/ozon.png',
        self::WB => '/icons/wb.png',
    ];
}
