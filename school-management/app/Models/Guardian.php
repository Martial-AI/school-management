<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    protected $fillable = ['first_name', 'last_name', 'relationship', 'phone', 'email', 'address'];
    public function students(): BelongsToMany { return $this->belongsToMany(Student::class)->withPivot(['is_primary_contact', 'can_pick_up']); }
}
