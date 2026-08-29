<?php

namespace App\Models;

use App\Enums\VillageTroopOrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'village_id',
    'unit_id',
    'order_type',
    'requested_quantity',
    'target_level',
    'use_hero_resources',
    'status',
    'execute_after',
    'claimed_at',
    'submitted_at',
    'cancelled_at',
    'accepted_quantity',
    'result_message',
])]
class VillageTroopOrder extends Model
{
    public const string TypeTraining = 'training';

    public const string TypeResearch = 'research';

    public const string TypeSmithy = 'smithy';

    /** @var array<string, mixed> */
    protected $attributes = [
        'order_type' => self::TypeTraining,
        'requested_quantity' => 1,
        'use_hero_resources' => false,
        'status' => VillageTroopOrderStatus::Scheduled,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'unit_id' => 'integer',
            'requested_quantity' => 'integer',
            'target_level' => 'integer',
            'use_hero_resources' => 'boolean',
            'accepted_quantity' => 'integer',
            'status' => VillageTroopOrderStatus::class,
            'execute_after' => 'immutable_datetime',
            'claimed_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
