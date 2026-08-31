<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    protected $fillable = ['academic_year_id', 'training_starts_on', 'training_ends_on', 'name', 'level', 'duration_months', 'monthly_fee_due_day', 'monthly_fee_amount', 'fee_mode', 'registration_fee_amount', 'registration_fee_due_date', 'capacity', 'homeroom_teacher_id'];

    protected function casts(): array
    {
        return ['training_starts_on' => 'date', 'training_ends_on' => 'date', 'registration_fee_due_date' => 'date', 'registration_fee_amount' => 'decimal:2', 'monthly_fee_amount' => 'decimal:2'];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }
}
