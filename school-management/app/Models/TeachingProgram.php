<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingProgram extends Model
{
    protected $fillable = ['teacher_id', 'school_class_id', 'subject_id', 'period_name', 'starts_on', 'ends_on', 'title', 'objectives'];
    protected function casts(): array { return ['starts_on' => 'date', 'ends_on' => 'date']; }
    public function lessons(): HasMany { return $this->hasMany(ProgramLesson::class)->orderBy('position'); }
    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
}
