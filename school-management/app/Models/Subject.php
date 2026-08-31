<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['name', 'code'];

    public function localizedName(): string
    {
        return match ($this->name) {
            'Français' => __('French language'),
            'Anglais' => __('English language'),
            'Espagnol', 'Espagnole', 'Espagnoles' => __('Spanish language'),
            'Italien', 'Italienne', 'Italiens' => __('Italian language'),
            'Informatique', 'Informatiques' => __('Computer science'),
            default => $this->name,
        };
    }
}
