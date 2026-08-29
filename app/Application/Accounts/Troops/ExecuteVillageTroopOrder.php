<?php

namespace App\Application\Accounts\Troops;

use App\Application\Accounts\Hero\UseHeroResourcesForCost;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Troops\Data\ParsedTrainingPage;
use App\Application\Accounts\Troops\Data\ParsedTrainingUnit;
use App\Application\Accounts\Troops\Parsers\SmithyPageParser;
use App\Application\Accounts\Troops\Parsers\TrainingBuildingPageParser;
use App\Application\Travian\TravianTroopCatalog;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Enums\VillageTroopOrderStatus;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use App\Models\VillageTroopOrder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ExecuteVillageTroopOrder
{
    public function __construct(
        protected TrainingBuildingPageParser $trainingBuildingPageParser,
        protected SmithyPageParser $smithyPageParser,
        protected UseHeroResourcesForCost $useHeroResourcesForCost,
    ) {}

    public function handle(Account $account, VillageTroopOrder $order, AccountSession $session): void
    {
        $order = $this->claim($order);

        if (! $order instanceof VillageTroopOrder) {
            return;
        }

        $village = $order->village()->with('runtimeState', 'buildings')->firstOrFail();

        try {
            if ($order->order_type === VillageTroopOrder::TypeTraining) {
                $this->executeTraining($account, $order, $session);

                return;
            }

            if ($order->order_type === VillageTroopOrder::TypeSmithy) {
                $this->executeSmithy($account, $order, $session);

                return;
            }

            $this->fail($order, 'This troop order type is not supported.');
        } catch (Throwable $throwable) {
            if ($order->fresh()?->status === VillageTroopOrderStatus::Claimed) {
                $this->fail($order, $throwable->getMessage());
            }

            throw $throwable;
        }
    }

    protected function executeTraining(Account $account, VillageTroopOrder $order, AccountSession $session): void
    {
        $village = $order->village()->with('runtimeState', 'buildings')->firstOrFail();
        $definition = TravianTroopCatalog::definition($order->unit_id);
        $buildingGid = (int) ($definition['training_building_gid'] ?? 0);
        $slot = $this->buildingSlot($village, $buildingGid);

        if ($slot === null) {
            $this->fail($order, 'The required training building is not present in the latest village layout.');

            return;
        }

        $pageResponse = $session->get($this->buildUri($slot, $buildingGid), $this->documentRequestOptions());
        $page = $this->trainingBuildingPageParser->parse($pageResponse->body);

        if (! $page instanceof ParsedTrainingPage || $page->actionUri === null) {
            $this->retryBeforeSubmission($order, 'The training page was temporarily unavailable; the order will retry safely.');
        }

        $unit = collect($page->units)->firstWhere('unitId', $order->unit_id);

        if (! $unit instanceof ParsedTrainingUnit) {
            $this->fail($order, 'Travian no longer exposes this unit as trainable.');

            return;
        }

        if ($unit->maxTrainable < 1) {
            $this->fail($order, 'Current village resources cannot train one unit.');

            return;
        }

        $payload = $page->hiddenFields;

        foreach ($page->units as $availableUnit) {
            $payload[$availableUnit->inputName] = '0';
        }

        $payload[$unit->inputName] = (string) $order->requested_quantity;
        $payload['s1'] = 'ok';
        $beforeQuantity = $this->queuedQuantity($page, $unit->unitId);
        $response = $session->postForm(
            $page->actionUri,
            $payload,
            $this->documentRequestOptions($pageResponse->effectiveUri),
        );
        $afterPage = $this->trainingBuildingPageParser->parse($response->body);
        $acceptedQuantity = max(0, $this->queuedQuantity($afterPage, $unit->unitId) - $beforeQuantity);

        if ($acceptedQuantity < 1) {
            $this->fail($order, 'Travian did not add any units to the training queue.');

            return;
        }

        $message = $acceptedQuantity < $order->requested_quantity
            ? "Travian accepted {$acceptedQuantity} of {$order->requested_quantity} requested units."
            : "Travian accepted all {$acceptedQuantity} requested units.";

        $order->forceFill([
            'status' => VillageTroopOrderStatus::Submitted,
            'submitted_at' => now(),
            'accepted_quantity' => $acceptedQuantity,
            'result_message' => $message,
        ])->save();

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Train,
            'status' => ActivityLogStatus::Done,
            'payload' => [
                'order_id' => $order->id,
                'unit_id' => $order->unit_id,
                'requested_quantity' => $order->requested_quantity,
                'accepted_quantity' => $acceptedQuantity,
                'building_gid' => $buildingGid,
            ],
            'message' => $message,
            'executed_at' => now(),
        ]);
    }

    protected function executeSmithy(Account $account, VillageTroopOrder $order, AccountSession $session): void
    {
        $village = $order->village()->with('buildings')->firstOrFail();
        $slot = $this->buildingSlot($village, 13);

        if ($slot === null) {
            $this->fail($order, 'The Smithy is not present in the latest village layout.');

            return;
        }

        $pageResponse = $session->get($this->buildUri($slot, 13), $this->documentRequestOptions());
        $page = $this->smithyPageParser->parse($pageResponse->body);
        $unit = collect($page->units)->firstWhere('unitId', $order->unit_id);

        if ($unit === null) {
            $this->retryBeforeSubmission($order, 'The Smithy page did not expose this unit; the order will retry safely.');
        }

        if (($unit->currentLevel ?? 0) >= (int) $order->target_level) {
            $this->submitSmithyResult($account, $order, 'The requested Smithy level was already completed.');

            return;
        }

        if ($page->queue !== []) {
            $this->fail($order, 'The Smithy started another improvement before this order executed.');

            return;
        }

        if ($unit->actionUri === null) {
            if ($order->use_hero_resources && $unit->hasResourceShortage && $this->useHeroResourcesForCost->handleCost(
                account: $account,
                village: $village,
                session: $session,
                requiredResources: $unit->cost,
                referer: $pageResponse->effectiveUri,
                purpose: 'Smithy improvement',
                manualOverride: true,
                contextPayload: ['order_id' => $order->id, 'unit_id' => $order->unit_id, 'target_level' => $order->target_level],
            )) {
                $pageResponse = $session->get($this->buildUri($slot, 13), $this->documentRequestOptions($pageResponse->effectiveUri));
                $page = $this->smithyPageParser->parse($pageResponse->body);
                $unit = collect($page->units)->firstWhere('unitId', $order->unit_id);
            }
        }

        if ($unit === null || $unit->actionUri === null) {
            $message = trim((string) $unit?->serverMessage);
            $this->fail($order, $message !== '' ? $message : 'Travian does not currently allow this Smithy improvement.');

            return;
        }

        $response = $session->get($unit->actionUri, $this->documentRequestOptions($pageResponse->effectiveUri));
        $afterPage = $this->smithyPageParser->parse($response->body);
        $confirmed = collect($afterPage->queue)->contains(
            static fn ($entry): bool => $entry->unitId === $order->unit_id
                && ($entry->targetLevel ?? 0) >= (int) $order->target_level,
        );
        $afterUnit = collect($afterPage->units)->firstWhere('unitId', $order->unit_id);

        if (! $confirmed && ($afterUnit?->currentLevel ?? 0) < (int) $order->target_level) {
            $this->fail($order, 'Travian did not confirm the Smithy improvement after submission.');

            return;
        }

        $this->submitSmithyResult($account, $order, "Smithy improvement to level {$order->target_level} was accepted.");
    }

    protected function submitSmithyResult(Account $account, VillageTroopOrder $order, string $message): void
    {
        $order->forceFill([
            'status' => VillageTroopOrderStatus::Submitted,
            'submitted_at' => now(),
            'accepted_quantity' => 1,
            'result_message' => $message,
        ])->save();

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $order->village_id,
            'activity_type' => ActivityType::Train,
            'status' => ActivityLogStatus::Done,
            'payload' => [
                'order_id' => $order->id,
                'order_type' => VillageTroopOrder::TypeSmithy,
                'unit_id' => $order->unit_id,
                'target_level' => $order->target_level,
                'building_gid' => 13,
            ],
            'message' => $message,
            'executed_at' => now(),
        ]);
    }

    protected function claim(VillageTroopOrder $order): ?VillageTroopOrder
    {
        return DB::transaction(function () use ($order): ?VillageTroopOrder {
            $lockedOrder = VillageTroopOrder::query()->lockForUpdate()->find($order->id);

            if (! $lockedOrder instanceof VillageTroopOrder || $lockedOrder->status !== VillageTroopOrderStatus::Scheduled || $lockedOrder->cancelled_at !== null) {
                return null;
            }

            $lockedOrder->forceFill([
                'status' => VillageTroopOrderStatus::Claimed,
                'claimed_at' => now(),
            ])->save();

            return $lockedOrder;
        });
    }

    protected function queuedQuantity(ParsedTrainingPage $page, int $unitId): int
    {
        return (int) collect($page->queue)->where('unitId', $unitId)->sum('quantity');
    }

    protected function fail(VillageTroopOrder $order, string $message): void
    {
        $order->forceFill([
            'status' => VillageTroopOrderStatus::Failed,
            'result_message' => $message,
        ])->save();
    }

    protected function retryBeforeSubmission(VillageTroopOrder $order, string $message): never
    {
        $order->forceFill([
            'status' => VillageTroopOrderStatus::Scheduled,
            'claimed_at' => null,
            'result_message' => $message,
        ])->save();

        throw new RuntimeException($message);
    }

    protected function buildingSlot(Village $village, int $buildingGid): ?int
    {
        $building = $village->buildings->first(
            static fn ($building): bool => (int) $building->building_gid === $buildingGid
                && (int) $building->slot_id >= 19
                && (int) $building->slot_id <= 40,
        );

        return $building === null ? null : (int) $building->slot_id;
    }

    protected function buildUri(int $slot, int $buildingGid): string
    {
        return (string) config('travian.paths.build', '/build.php').'?id='.$slot.'&gid='.$buildingGid;
    }

    /** @return array<string, mixed> */
    protected function documentRequestOptions(?string $referer = null): array
    {
        return [
            'headers' => array_filter([
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Referer' => $referer,
            ]),
            'allow_redirects' => ['max' => 5, 'strict' => false, 'referer' => true],
        ];
    }
}
