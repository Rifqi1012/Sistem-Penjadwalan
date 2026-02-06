<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_name',
        'order_date',
        'start_date',
        'bales',
        'pcs_total',
        'pcs_remaining',
        'status'
    ];
}
