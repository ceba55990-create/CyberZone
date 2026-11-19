<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Computer extends Model
{
    public const STATUSES = [
        'available' => 'Вільний',
        'occupied' => 'Зайнятий',
        'maintenance' => 'Обслуговування',
    ];

    protected $fillable = [
        'name',
        'cpu',
        'gpu',
        'ram_gb',
        'storage_gb',
        'hourly_rate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'ram_gb' => 'integer',
            'storage_gb' => 'integer',
            'hourly_rate' => 'decimal:2',
        ];
    }
}
