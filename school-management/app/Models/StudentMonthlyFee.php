<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMonthlyFee extends Model
{
    protected $fillable = ['student_id', 'fee_month', 'amount', 'paid_at', 'paid_by', 'receipt_number'];

    protected function casts(): array
    {
        return ['fee_month' => 'date', 'paid_at' => 'datetime', 'amount' => 'decimal:2'];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function paidBy(): BelongsTo { return $this->belongsTo(User::class, 'paid_by'); }
}
