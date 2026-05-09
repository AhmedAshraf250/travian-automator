<?php

namespace App\Application\Accounts\Sync;

use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Sync\Parsers\Dorf1OverviewParser;
use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Synchronizes account and village overview data from the live Travian dorf1 page.
 */
class SyncAccountOverview
{
    /**
     * Synchronize the given account overview.
     */
    public function handle(Account $account): void
    {
        $account->forceFill([
            'status' => AccountStatus::Syncing,
        ])->save();

        try {
            $session = app(AccountSessionFactory::class)->for($account);

            app(TravianLoginAction::class)->handle($account, $session);

            $response = $session->get('/dorf1.php');
            $this->dumpDorf1Response($account, $response->body, $response->effectiveUri);
            $parsedOverview = app(Dorf1OverviewParser::class)->parse($response->body);

            DB::transaction(function () use ($account, $parsedOverview): void {
                foreach ($parsedOverview->villages as $parsedVillage) {
                    $resolvedVillage = $parsedVillage->travianVillageId === $parsedOverview->activeVillage->travianVillageId
                        ? $parsedOverview->activeVillage
                        : $parsedVillage;

                    $village = $account->villages()->updateOrCreate(
                        ['travian_village_id' => $resolvedVillage->travianVillageId],
                        [
                            'name' => $resolvedVillage->name,
                            'x' => $resolvedVillage->x,
                            'y' => $resolvedVillage->y,
                            'population' => $resolvedVillage->population ?? 0,
                            'is_active' => true,
                            'last_sync_at' => now(),
                        ],
                    );

                    $village->settings()->updateOrCreate(
                        [],
                        [
                            'field_priority' => ['wood' => 1, 'clay' => 2, 'iron' => 3, 'crop' => 4],
                        ],
                    );

                    if ($resolvedVillage->travianVillageId === $parsedOverview->activeVillage->travianVillageId) {
                        $village->resourceState()->updateOrCreate(
                            [],
                            [
                                'wood' => $parsedOverview->resourceState->wood,
                                'clay' => $parsedOverview->resourceState->clay,
                                'iron' => $parsedOverview->resourceState->iron,
                                'crop' => $parsedOverview->resourceState->crop,
                                'wood_production' => $parsedOverview->resourceState->woodProduction,
                                'clay_production' => $parsedOverview->resourceState->clayProduction,
                                'iron_production' => $parsedOverview->resourceState->ironProduction,
                                'crop_production' => $parsedOverview->resourceState->cropProduction,
                                'warehouse_capacity' => $parsedOverview->resourceState->warehouseCapacity,
                                'granary_capacity' => $parsedOverview->resourceState->granaryCapacity,
                                'simulated_at' => now(),
                                'server_reported_at' => now(),
                            ],
                        );

                        $village->runtimeState()->updateOrCreate(
                            [],
                            [
                                'tribe_id' => $parsedOverview->runtimeState->tribeId,
                                'troop_slots' => $parsedOverview->runtimeState->troopSlots,
                                'incoming_attack_count' => $parsedOverview->runtimeState->incomingAttackCount,
                                'incoming_reinforcement_count' => $parsedOverview->runtimeState->incomingReinforcementCount,
                                'outgoing_movement_count' => $parsedOverview->runtimeState->outgoingMovementCount,
                                'movement_entries' => array_map(
                                    static fn ($entry): array => [
                                        'kind' => $entry->kind,
                                        'label' => $entry->label,
                                        'count' => $entry->count,
                                        'remaining_seconds' => $entry->remainingSeconds,
                                        'remaining_label' => $entry->remainingLabel,
                                    ],
                                    $parsedOverview->runtimeState->movementEntries,
                                ),
                                'construction_entries' => array_map(
                                    static fn ($entry): array => [
                                        'building_name' => $entry->buildingName,
                                        'target_level' => $entry->targetLevel,
                                        'remaining_seconds' => $entry->remainingSeconds,
                                        'remaining_label' => $entry->remainingLabel,
                                        'finish_label' => $entry->finishLabel,
                                    ],
                                    $parsedOverview->runtimeState->constructionEntries,
                                ),
                                'hero_status' => $parsedOverview->runtimeState->heroStatus,
                                'hero_remaining_seconds' => $parsedOverview->runtimeState->heroRemainingSeconds,
                                'server_reported_at' => now(),
                            ],
                        );

                        $village->buildings()
                            ->where('slot_id', '>=', 200)
                            ->delete();

                        foreach ($parsedOverview->constructionQueue as $queueIndex => $constructionQueueEntry) {
                            $village->buildings()->updateOrCreate(
                                ['slot_id' => 200 + $queueIndex],
                                [
                                    'building_type' => $constructionQueueEntry->buildingName,
                                    'current_level' => $constructionQueueEntry->targetLevel,
                                    'is_under_construction' => true,
                                    'finish_at' => now()->addSeconds($constructionQueueEntry->remainingSeconds),
                                ],
                            );
                        }
                    }
                }

                $account->forceFill([
                    'status' => AccountStatus::Active,
                    'is_active' => true,
                    'last_sync_at' => now(),
                    'last_login_at' => now(),
                    'last_error_at' => null,
                    'last_error_message' => null,
                ])->save();

                ActivityLog::query()->create([
                    'account_id' => $account->id,
                    'activity_type' => ActivityType::Sync,
                    'status' => ActivityLogStatus::Done,
                    'message' => 'Account overview synced successfully from dorf1.',
                    'executed_at' => now(),
                ]);
            });

            $session->persist();
        } catch (Throwable $throwable) {
            $message = $this->normalizeSyncErrorMessage($throwable);

            $account->forceFill([
                'status' => AccountStatus::Error,
                'last_error_at' => now(),
                'last_error_message' => $message,
            ])->save();

            ActivityLog::query()->create([
                'account_id' => $account->id,
                'activity_type' => ActivityType::Sync,
                'status' => ActivityLogStatus::Failed,
                'message' => $message,
                'executed_at' => now(),
            ]);

            throw $throwable;
        }
    }

    /**
     * Normalize low-level transport errors into actionable sync messages.
     */
    protected function normalizeSyncErrorMessage(Throwable $throwable): string
    {
        $message = $throwable->getMessage();

        if ($throwable instanceof RequestException && str_contains($message, 'cURL error 60')) {
            return 'SSL verification failed. Configure TRAVIAN_HTTP_CA_BUNDLE in .env with a valid cacert.pem path, or fix the PHP/cURL CA store.';
        }

        if ($throwable instanceof RuntimeException && str_contains($message, 'TRAVIAN_HTTP_CA_BUNDLE points to a non-existent file:')) {
            return $message;
        }

        return $message;
    }

    /**
     * Persist the live dorf1 response for debugging when enabled.
     */
    protected function dumpDorf1Response(Account $account, string $html, string $effectiveUri): void
    {
        if (! config('travian.debug.dump_dorf1_response')) {
            return;
        }

        $timestamp = now()->format('Ymd_His');
        $accountSlug = Str::slug($account->username) ?: 'account-'.$account->id;
        $directory = "debug/travian/{$accountSlug}";
        $baseFilename = "{$timestamp}_dorf1";

        $metadata = [
            'account_id' => $account->id,
            'account_username' => $account->username,
            'effective_uri' => $effectiveUri,
            'contains_view_data' => str_contains($html, 'viewData:'),
            'contains_population_markup' => str_contains($html, 'class="population"'),
            'contains_coordinates_markup' => str_contains($html, 'coordinatesGrid'),
            'contains_building_list' => str_contains($html, 'class="buildingList"'),
            'contains_resources_object' => str_contains($html, 'var resources = {'),
            'captured_at' => now()->toIso8601String(),
        ];

        Storage::disk('local')->put("{$directory}/{$baseFilename}.html", $html);
        Storage::disk('local')->put(
            "{$directory}/{$baseFilename}.json",
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'activity_type' => ActivityType::Sync,
            'status' => ActivityLogStatus::Done,
            'message' => "Debug dorf1 dump saved to storage/app/{$directory}/{$baseFilename}.html",
            'executed_at' => now(),
        ]);
    }
}
