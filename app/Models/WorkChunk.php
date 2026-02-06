<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkChunk extends Model
{
    protected $fillable = ['order_id', 'work_date', 'pcs', 'status'];
}
