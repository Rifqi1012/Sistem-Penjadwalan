<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitAssignment extends Model
{
    protected $fillable = ['work_chunk_id', 'unit_id', 'sequence'];
}
