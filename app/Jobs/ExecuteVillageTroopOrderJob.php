<?php

namespace App\Jobs;

use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSessionFactory;
use App\Application\Accounts\Troops\ExecuteVillageTroopOrder;
use App\Enums\VillageTroopOrderStatus;
use App\Models\Account;
use App\Models\SystemSetting;
use App\Models\VillageTroopOrder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class ExecuteVillageTroopOrderJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 90;

    /** @var list<int> */
    public array $backoff = [10, 30, 60, 120];

    public int $uniqueFor = 600;

    public function __construct(public int $accountId, public int $orderId) {}

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("travian-account:{$this->accountId}"))
                ->shared()
                ->releaseAfter(15)
                ->expireAfter(600),
        ];
    }

    public function handle(
        AccountSessionFactory $accountSessionFactory,
        TravianLoginAction $travianLoginAction,
        ExecuteVillageTroopOrder $executeVillageTroopOrder,
    ): void {
        $order = VillageTroopOrder::query()->with('village.account')->findOrFail($this->orderId);

        if ($order->status !== VillageTroopOrderStatus::Scheduled || $order->cancelled_at !== null) {
            return;
        }

        if ($order->execute_after->isFuture()) {
            $this->release((int) now()->diffInSeconds($order->execute_after, false));

            return;
        }

        $account = Account::query()->findOrFail($this->accountId);

        if (! SystemSetting::automationEnabled() || ! $account->is_active || $account->is_archived || ! $order->village->is_active) {
            $order->forceFill([
                'status' => VillageTroopOrderStatus::Failed,
                'result_message' => 'Program, account or village was paused before submission.',
            ])->save();

            return;
        }

        $session = $accountSessionFactory->for($account);
        $travianLoginAction->handle($account, $session);
        $session->get(
            (string) config('travian.paths.overview', '/dorf1.php')
            .'?newdid='.rawurlencode((string) $order->village->travian_village_id),
        );
        $executeVillageTroopOrder->handle($account, $order, $session);
        $session->persist();
    }

    public function failed(?Throwable $throwable): void
    {
        VillageTroopOrder::query()
            ->whereKey($this->orderId)
            ->whereIn('status', [
                VillageTroopOrderStatus::Scheduled->value,
                VillageTroopOrderStatus::Claimed->value,
            ])
            ->update([
                'status' => VillageTroopOrderStatus::Failed->value,
                'result_message' => $throwable?->getMessage() ?? 'Troop order job failed.',
            ]);
    }
}
