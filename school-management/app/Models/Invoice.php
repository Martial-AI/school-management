<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['reference', 'invoice_type_id', 'student_id', 'school_class_id', 'user_id', 'amount_due', 'amount_paid', 'due_date', 'status', 'description'];

    protected function casts(): array
    {
        return ['amount_due' => 'decimal:2', 'amount_paid' => 'decimal:2', 'due_date' => 'date'];
    }
}
