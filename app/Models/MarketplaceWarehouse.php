<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceWarehouse extends Model
{
    protected $table = 'marketplace_warehouses';

    protected $fillable = [
        'name',
        'marketplace_id',
        'cluster',
        'warehouse_id',
        'macrolocal_cluster_id',
    ];
}
