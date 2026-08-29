<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'village_id',
    'units',
    'training_queues',
    'research_queue',
    'smithy_queue',
    'pages',
    'server_reported_at',
])]
class VillageTroopSnapshot extends Model
{
    public function smithyLevelFor(int $unitId): int
    {
        $observedUnit = data_get($this->units, (string) $unitId, []);

        return (int) (data_get($observedUnit, 'smithy.current_level')
            ?? data_get($observedUnit, 'training.smithy_level', 0));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'units' => 'array',
            'training_queues' => 'array',
            'research_queue' => 'array',
            'smithy_queue' => 'array',
            'pages' => 'array',
            'server_reported_at' => 'immutable_datetime',
        ];
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
