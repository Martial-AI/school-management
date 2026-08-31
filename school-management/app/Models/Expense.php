<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = ['reference', 'category', 'reason', 'amount', 'spent_on', 'spent_at', 'proof_path', 'recorded_by', 'employee_payment_id'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'spent_on' => 'date', 'spent_at' => 'datetime']; }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function employeePayment(): BelongsTo { return $this->belongsTo(EmployeePayment::class); }
}
