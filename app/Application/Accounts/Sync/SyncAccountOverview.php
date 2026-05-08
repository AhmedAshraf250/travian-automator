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
            $parsedOverview = app(Dorf1OverviewParser::class)->parse($response->body);

            DB::transaction(function () use ($account, $parsedOverview): void {
                foreach ($parsedOverview->villages as $parsedVillage) {
                    $village = $account->villages()->updateOrCreate(
                        ['travian_village_id' => $parsedVillage->travianVillageId],
                        [
                            'name' => $parsedVillage->name,
                            'x' => $parsedVillage->x,
                            'y' => $parsedVillage->y,
                            'population' => $parsedVillage->population ?? 0,
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

                    if ($parsedVillage->travianVillageId === $parsedOverview->activeVillage->travianVillageId) {
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
}
