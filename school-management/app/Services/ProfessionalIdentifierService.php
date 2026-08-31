<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class ProfessionalIdentifierService
{
    public function next(): string
    {
        $year = Carbon::now()->format('y');
        $prefix = "PA{$year}-";
        $numbers = User::query()->where('professional_number', 'like', "{$prefix}%")->lockForUpdate()->pluck('professional_number');
        $sequence = $numbers->map(fn (string $number): int => (int) substr($number, -3))->max() + 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
