<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePayment extends Model
{
    protected $fillable = ['user_id','reference','amount','due_date','paid_at','status','recorded_by','payment_method','payment_phone','transaction_id','bank_details'];
    protected function casts(): array { return ['amount'=>'decimal:2','due_date'=>'date','paid_at'=>'datetime']; }

    public function user() { return $this->belongsTo(User::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }
    public function expense() { return $this->hasOne(Expense::class); }
}
