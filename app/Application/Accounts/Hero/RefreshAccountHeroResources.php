<?php

namespace App\Application\Accounts\Hero;

use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Models\Account;
use App\Models\AccountHeroState;
use RuntimeException;

class RefreshAccountHeroResources
{
    public function __construct(
        protected AccountSessionFactory $accountSessionFactory,
        protected TravianLoginAction $travianLoginAction,
        protected UseHeroResourcesForConstruction $heroResources,
    ) {}

    /**
     * Refresh and persist the account-wide Hero resource inventory.
     *
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    public function handle(Account $account): array
    {
        $account = $account->fresh('heroState');

        if (! $account instanceof Account || ! $account->is_active) {
            throw new RuntimeException('The account is not available for a Hero inventory refresh.');
        }

        $session = $this->accountSessionFactory->for($account);
        $this->travianLoginAction->handle($account, $session);
        $referer = rtrim($account->server_url, '/').'/'.ltrim((string) config('travian.paths.overview', '/dorf1.php'), '/');
        $snapshot = $this->heroResources->readAvailableResources($session, $referer);
        $session->persist();

        if ($snapshot === null) {
            throw new RuntimeException('Travian did not return the Hero resource inventory.');
        }

        $heroState = $account->heroState ?? new AccountHeroState(['account_id' => $account->id]);
        $payload = is_array($heroState->payload) ? $heroState->payload : [];
        $payload['resource_inventory'] = [
            ...$snapshot['resources'],
            'travian_village_id' => $snapshot['travian_village_id'],
            'reported_at' => now()->toIso8601String(),
        ];

        $heroState->forceFill([
            'account_id' => $account->id,
            'payload' => $payload,
        ])->save();

        return $snapshot['resources'];
    }
}
