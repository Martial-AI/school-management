<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceType extends Model
{
    protected $fillable = ['code', 'name', 'is_salary', 'is_active'];
}
