<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramLesson extends Model
{
    protected $fillable = ['teaching_program_id', 'parent_id', 'title', 'description', 'position', 'planned_on', 'completed_at', 'completion_note'];
    protected function casts(): array { return ['planned_on' => 'date', 'completed_at' => 'datetime']; }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('position'); }
}
