<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = ['student_id', 'school_class_id', 'enrolled_on', 'status', 'notes'];
    protected function casts(): array { return ['enrolled_on' => 'date']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class); }
}
