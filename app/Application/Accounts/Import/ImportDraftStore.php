<?php

namespace App\Application\Accounts\Import;

use App\Models\ImportDraft;
use Illuminate\Support\Facades\Schema;

/**
 * Persists the latest bulk import textarea contents between page refreshes.
 */
class ImportDraftStore
{
    public const BULK_IMPORT_KEY = 'bulk-account-import';

    /**
     * Retrieve the saved draft contents.
     */
    public function get(string $key = self::BULK_IMPORT_KEY): string
    {
        if (! Schema::hasTable('import_drafts')) {
            return '';
        }

        return ImportDraft::query()
            ->where('key', $key)
            ->value('contents') ?? '';
    }

    /**
     * Save the latest draft contents.
     */
    public function put(string $contents, string $key = self::BULK_IMPORT_KEY): void
    {
        if (! Schema::hasTable('import_drafts')) {
            return;
        }

        ImportDraft::query()->updateOrCreate(
            ['key' => $key],
            ['contents' => $contents],
        );
    }
}
