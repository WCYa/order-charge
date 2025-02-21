<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['order_name', 'date', 'data', 'items_amount', 'total_price', 'secret'];

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    /*
     * Local Scope
     */
    public function scopeRecentOrders(Builder $query): void
    {
        $query->orderBy('id', 'desc')->limit(10);
    }
}
