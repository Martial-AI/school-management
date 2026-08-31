<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeletedItem extends Model
{
    protected $fillable = ['type', 'label', 'payload', 'deleted_by', 'deleted_at'];
    protected function casts(): array { return ['payload' => 'array', 'deleted_at' => 'datetime']; }
    public function deletedBy(): BelongsTo { return $this->belongsTo(User::class, 'deleted_by'); }

    public function localizedType(): string
    {
        return match ($this->type) {
            'student' => __('Student'),
            'school_class' => __('Class'),
            'user' => __('Account'),
            'program' => __('Program'),
            'login_history' => __('Connection history'),
            'activity' => __('Activity'),
            default => $this->type,
        };
    }

    public function localizedLabel(): string
    {
        $prefixes = [
            'Élève' => __('Student'),
            'Classe' => __('Class'),
            'Compte' => __('Account'),
            'Programme' => __('Program'),
            'Historique de connexion' => __('Connection history'),
            'Notification' => __('Notification'),
        ];

        foreach ($prefixes as $storedPrefix => $localizedPrefix) {
            if (str_starts_with($this->label, $storedPrefix.' :')) {
                return $localizedPrefix.' :'.substr($this->label, strlen($storedPrefix.' :'));
            }
        }

        return $this->label;
    }
}
