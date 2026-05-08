<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Persists sensitive import drafts so the UI survives refreshes safely.
 */
#[Fillable(['key', 'contents'])]
class ImportDraft extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contents' => 'encrypted',
        ];
    }
}
