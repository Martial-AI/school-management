<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Student extends Model
{
    protected $fillable = ['student_number', 'first_name', 'last_name', 'gender', 'birth_date', 'birth_place', 'phone', 'address', 'monthly_fee_amount', 'photo_path', 'status'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'status' => StudentStatus::class];
    }

    protected static function booted(): void
    {
        static::creating(function (self $student): void { $student->qr_token ??= (string) Str::uuid(); });
    }

    public function guardians(): BelongsToMany { return $this->belongsToMany(Guardian::class)->withPivot(['is_primary_contact', 'can_pick_up']); }
    public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
    public function documents(): HasMany { return $this->hasMany(StudentDocument::class); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function monthlyFees(): HasMany { return $this->hasMany(StudentMonthlyFee::class); }
}
