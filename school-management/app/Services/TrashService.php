<?php

namespace App\Services;

use App\Models\DeletedItem;
use Illuminate\Database\Eloquent\Model;

class TrashService
{
    public static function store(string $type, Model $model, string $label, array $extra = []): DeletedItem
    {
        return DeletedItem::create([
            'type' => $type,
            'label' => $label,
            'payload' => ['attributes' => $model->getAttributes(), ...$extra],
            'deleted_by' => auth()->id(),
            'deleted_at' => now(),
        ]);
    }
}
