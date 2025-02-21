<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $fillable = ['name', 'items', 'total_price'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
