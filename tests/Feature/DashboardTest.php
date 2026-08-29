<?php

use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Jobs\CancelVillageDemolitionJob;
use App\Jobs\DemolishVillageBuildingJob;
use App\Jobs\RefreshVillageDemolitionSnapshotJob;
use App\Jobs\RefreshVillageMarketplaceSnapshotJob;
use App\Jobs\RunTravianAutomationJob;
use App\Jobs\SendManualMarketplaceTransferJob;
use App\Jobs\SyncTravianAccountJob;
use App\Livewire\Dashboard\Account\Row as AccountRow;
use App\Livewire\Dashboard\Index;
use App\Livewire\Dashboard\Modals\AccountSettings as AccountSettingsModal;
use App\Livewire\Dashboard\Modals\MarketplaceTransfer as MarketplaceTransferModal;
use App\Livewire\Dashboard\Modals\VillageSettings as VillageSettingsModal;
use App\Livewire\Dashboard\Village\Row as VillageRow;
use App\Models\Account;
use App\Models\AccountProxy;
use App\Models\AccountSetting;
use App\Models\ActivityLog;
use App\Models\ImportDraft;
use App\Models\SystemSetting;
use App\Models\VillageBuildingTarget;
use App\Models\VillageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('dashboard page loads successfully', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSeeText('Travian Multi-Account Automation');
});

test('bulk import creates accounts and persists encrypted draft', function () {
    Queue::fake();

    Livewire::test(Index::class)
        ->call('openImportModal')
        ->set('bulkImportDraft', 'https://ts7.x1.arabics.travian.com/ marshal 12345678 127.0.0.1 8080 Mozilla/5.0')
        ->assertSee('Input account lines')
        ->assertSee('Preview generated from input')
        ->assertSee('server: https://ts7.x1.arabics.travian.com/')
        ->assertSee('user: marshal')
        ->call('importAccounts');

    $account = Account::query()->first();

    expect($account)->not->toBeNull();
    expect($account?->username)->toBe('marshal');
    expect($account?->proxy_scheme)->toBe('http');
    expect($account?->proxy_ip)->toBe('127.0.0.1');
    expect($account?->proxy_port)->toBe(8080);
    expect(AccountSetting::query()->count())->toBe(1);
    expect(ActivityLog::query()->count())->toBe(2);
    expect(ActivityLog::query()->where('message', 'Login and account sync queued from Accounts & Login.')->exists())->toBeTrue();
    expect(ImportDraft::query()->where('key', 'bulk-account-import')->exists())->toBeTrue();

    Queue::assertPushed(SyncTravianAccountJob::class, function (SyncTravianAccountJob $job) use ($account) {
        return $job->accountId === $account?->id
            && $job->villageId === null
            && $job->ignoreConnectionBackoff === true;
    });
});

test('bulk import stores proxy protocol and credentials from proxy url', function () {
    Queue::fake();

    Livewire::test(Index::class)
        ->call('openImportModal')
        ->set('bulkImportDraft', '!https://ts7.x1.arabics.travian.com/!marshal!12345678!socks5://proxy-user:proxy-pass@127.0.0.1:1080!Mozilla/5.0')
        ->assertSee('proxy: socks5://127.0.0.1:1080')
        ->call('importAccounts');

    $account = Account::query()->first();

    expect($account)->not->toBeNull();
    expect($account?->proxy_scheme)->toBe('socks5');
    expect($account?->proxy_ip)->toBe('127.0.0.1');
    expect($account?->proxy_port)->toBe(1080);
    expect($account?->proxy_username)->toBe('proxy-user');
    expect($account?->proxy_password)->toBe('proxy-pass');
    expect($account?->active_account_proxy_id)->not->toBeNull();
    expect($account?->proxies()->count())->toBe(1);
});

test('account settings can manage a proxy pool and choose the active proxy', function () {
    $account = Account::factory()->create([
        'username' => 'marshal',
    ]);
    $account->settings()->create();

    Livewire::test(AccountSettingsModal::class)
        ->call('openAccountSettingsModal', $account->id)
        ->call('setAccountSettingsTab', 'proxies')
        ->call('addAccountProxyDraft')
        ->set('accountProxyDrafts.0.scheme', 'socks5')
        ->set('accountProxyDrafts.0.host', '10.0.0.1')
        ->set('accountProxyDrafts.0.port', '1080')
        ->set('accountProxyDrafts.0.username', 'proxy-user')
        ->set('accountProxyDrafts.0.password', 'proxy-pass')
        ->set('accountActiveProxyDraft', 'new:0')
        ->call('addAccountProxyDraft')
        ->set('accountProxyDrafts.1.scheme', 'http')
        ->set('accountProxyDrafts.1.host', '10.0.0.2')
        ->set('accountProxyDrafts.1.port', '8080')
        ->set('accountActiveProxyDraft', 'new:0')
        ->call('saveAccountSettings');

    $account->refresh();
    $proxies = $account->proxies()->orderBy('position')->get();

    expect($proxies)->toHaveCount(2);
    expect($account->active_account_proxy_id)->toBe($proxies[0]->id);
    expect($account->proxy_scheme)->toBe('socks5');
    expect($account->proxy_ip)->toBe('10.0.0.1');
    expect($account->proxy_port)->toBe(1080);
    expect($account->proxy_username)->toBe('proxy-user');
    expect($account->proxy_password)->toBe('proxy-pass');
});

test('account settings upgrades a legacy proxy into the pool and selects it', function () {
    $account = Account::factory()->create([
        'username' => 'legacy',
        'proxy_scheme' => 'http',
        'proxy_ip' => '47.82.77.82',
        'proxy_port' => 80,
        'proxy_username' => 'root',
        'proxy_password' => 'secret',
        'active_account_proxy_id' => null,
    ]);
    $account->settings()->create();

    Livewire::test(AccountSettingsModal::class)
        ->call('openAccountSettingsModal', $account->id)
        ->assertSet('accountActiveProxyDraft', 'proxy:'.$account->fresh()->active_account_proxy_id)
        ->assertSet('accountProxyDrafts.0.host', '47.82.77.82');
});

test('account settings can remove every proxy without recreating the legacy proxy', function () {
    $account = Account::factory()->create([
        'username' => 'clear-proxy',
        'proxy_scheme' => 'http',
        'proxy_ip' => '47.82.77.82',
        'proxy_port' => 80,
        'proxy_username' => 'root',
        'proxy_password' => 'secret',
    ]);
    $proxy = $account->proxies()->create([
        'scheme' => 'http',
        'host' => '47.82.77.82',
        'port' => 80,
        'username' => 'root',
        'password' => 'secret',
        'status' => 'active',
        'position' => 1,
    ]);
    $account->forceFill([
        'active_account_proxy_id' => $proxy->id,
    ])->save();
    $account->settings()->create();

    Livewire::test(AccountSettingsModal::class)
        ->call('openAccountSettingsModal', $account->id)
        ->call('removeAccountProxyDraft', 0)
        ->assertSet('accountActiveProxyDraft', 'direct')
        ->call('saveAccountSettings')
        ->call('openAccountSettingsModal', $account->id)
        ->assertSet('accountActiveProxyDraft', 'direct')
        ->assertSet('accountProxyDrafts', []);

    $account->refresh();

    expect($account->proxies()->count())->toBe(0);
    expect($account->proxy_ip)->toBeNull();
    expect($account->active_account_proxy_id)->toBeNull();
});

test('marking a cooled proxy as ready clears only its current failure window', function () {
    $account = Account::factory()->create([
        'username' => 'cooling-proxy',
    ]);
    $proxy = $account->proxies()->create([
        'scheme' => 'http',
        'host' => '47.82.77.82',
        'port' => 80,
        'status' => AccountProxy::StatusCooldown,
        'position' => 1,
        'failure_count' => 5,
        'lifetime_failure_count' => 12,
        'cooldown_until' => now()->addMinutes(5),
        'last_error_message' => 'timeout',
    ]);
    $account->settings()->create();

    Livewire::test(AccountSettingsModal::class)
        ->call('openAccountSettingsModal', $account->id)
        ->set('accountProxyDrafts.0.status', AccountProxy::StatusActive)
        ->set('accountActiveProxyDraft', 'proxy:'.$proxy->id)
        ->call('saveAccountSettings');

    $proxy->refresh();

    expect($proxy->status)->toBe(AccountProxy::StatusActive);
    expect($proxy->failure_count)->toBe(0);
    expect($proxy->lifetime_failure_count)->toBe(12);
    expect($proxy->cooldown_until)->toBeNull();
    expect($proxy->last_error_message)->toBeNull();
});

test('dashboard poll revives proxies whose cooldown elapsed', function () {
    Queue::fake();

    $account = Account::factory()->create();
    $proxy = $account->proxies()->create([
        'scheme' => 'http',
        'host' => '47.82.77.82',
        'port' => 80,
        'status' => AccountProxy::StatusCooldown,
        'position' => 1,
        'failure_count' => 5,
        'lifetime_failure_count' => 12,
        'cooldown_until' => now()->subSecond(),
        'last_error_message' => 'timeout',
    ]);

    Livewire::test(Index::class)
        ->call('refreshDashboardIfChanged');

    $proxy->refresh();

    expect($proxy->status)->toBe(AccountProxy::StatusActive);
    expect($proxy->failure_count)->toBe(0);
    expect($proxy->lifetime_failure_count)->toBe(12);
    expect($proxy->cooldown_until)->toBeNull();
    expect($proxy->last_error_message)->toBeNull();
});

test('dashboard poll recovers accounts stuck in syncing after a timed out job', function () {
    Queue::fake();
    config()->set('travian.automation.stale_syncing_minutes', 5);

    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'status' => AccountStatus::Syncing,
        'updated_at' => now()->subMinutes(10),
    ]);

    Livewire::test(Index::class)
        ->call('refreshDashboardIfChanged');

    $account->refresh();

    expect($account->status)->toBe(AccountStatus::Error);
    expect($account->last_error_message)->toBe('Background job timed out or stopped before it could finish.');
    expect(ActivityLog::query()
        ->where('account_id', $account->id)
        ->where('status', ActivityLogStatus::Failed)
        ->where('message', 'Background job appears stalled; account status recovered from syncing.')
        ->exists())->toBeTrue();
});

test('automation cycle recovers accounts stuck in syncing without an open dashboard', function () {
    Queue::fake();
    config()->set('travian.automation.stale_syncing_minutes', 5);

    $account = Account::factory()->create([
        'is_active' => true,
        'is_archived' => false,
        'status' => AccountStatus::Syncing,
        'updated_at' => now()->subMinutes(10),
    ]);

    $this->artisan('travian:automation-cycle')
        ->assertSuccessful();

    $account->refresh();

    expect($account->status)->toBe(AccountStatus::Error);
    expect($account->last_error_message)->toBe('Background job timed out or stopped before it could finish.');
});

test('dashboard shows imported account username', function () {
    $account = Account::factory()->create([
        'username' => 'strategist',
    ]);

    $account->settings()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('strategist');
});

test('village update queues village sync followed by village automation', function () {
    Queue::fake();

    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('requestVillageSync', $village->id);

    Queue::assertPushed(SyncTravianAccountJob::class, function (SyncTravianAccountJob $job) use ($account, $village) {
        return $job->accountId === $account->id
            && $job->villageId === $village->id
            && $job->ignoreConnectionBackoff === true
            && $job->useReloadAuto === false;
    });

    Queue::assertPushedWithChain(SyncTravianAccountJob::class, [
        new RunTravianAutomationJob($account->id, $village->id, false, true),
    ]);
});

test('village update ignores repeated clicks while account sync work is pending', function () {
    Queue::fake();

    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('requestVillageSync', $village->id)
        ->call('requestVillageSync', $village->id);

    Queue::assertPushed(SyncTravianAccountJob::class, 1);

    expect(ActivityLog::query()
        ->where('account_id', $account->id)
        ->where('village_id', $village->id)
        ->where('message', 'Village-only update requested and queued.')
        ->count())->toBe(1);
});

test('village timer sync is ignored while global automation is paused', function () {
    Queue::fake();
    SystemSetting::setAutomationEnabled(false);

    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'CR7',
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('queueVillageTimerSync', $village->id);

    Queue::assertNotPushed(SyncTravianAccountJob::class);

    expect(ActivityLog::query()
        ->where('village_id', $village->id)
        ->where('message', 'Village timer elapsed; sync queued automatically.')
        ->exists())->toBeFalse();
});

test('village transfer modal queues a manual marketplace transfer job', function () {
    Queue::fake();

    $account = Account::factory()->create();
    $sourceVillage = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'x' => 60,
        'y' => 19,
        'is_active' => true,
    ]);
    $destinationVillage = $account->villages()->create([
        'travian_village_id' => '26000',
        'name' => 'CR7',
        'x' => 9,
        'y' => 59,
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('openMarketplaceTransferModal', $sourceVillage->id)
        ->assertSee('Quick Send uses saved Trade Settings')
        ->assertSee('Sending from AMH7 [60|19]')
        ->assertSet('marketplaceDestinationVillageId', $destinationVillage->id)
        ->set('marketplaceWoodDraft', 500)
        ->set('marketplaceCropDraft', 200)
        ->call('queueManualMarketplaceTransfer');

    Queue::assertPushed(SendManualMarketplaceTransferJob::class, function (SendManualMarketplaceTransferJob $job) use ($account, $sourceVillage) {
        return $job->accountId === $account->id
            && $job->sourceVillageId === $sourceVillage->id
            && $job->x === 9
            && $job->y === 59
            && $job->resources === [
                'wood' => 500,
                'clay' => 0,
                'iron' => 0,
                'crop' => 200,
            ];
    });

    expect(ActivityLog::query()
        ->where('village_id', $sourceVillage->id)
        ->where('message', 'Manual marketplace transfer queued from dashboard.')
        ->exists())->toBeTrue();
});

test('isolated TR modal owns trade drafts and can switch to manual coordinates', function () {
    $account = Account::factory()->create();
    $sourceVillage = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'x' => 60,
        'y' => 19,
        'is_active' => true,
    ]);
    $sourceVillage->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'support_enabled' => true,
    ]);

    Livewire::test(MarketplaceTransferModal::class)
        ->dispatch('dashboard-open-marketplace-transfer', villageId: $sourceVillage->id)
        ->call('setMarketplaceTransferTab', 'settings')
        ->assertSee('Receive support')
        ->assertSet('villageSupplyResourcesDraft', true)
        ->call('setMarketplaceTransferTab', 'send')
        ->set('marketplaceDestinationMode', 'manual')
        ->assertSet('marketplaceDestinationMode', 'manual')
        ->assertSee('Manual coordinates')
        ->assertSee('X')
        ->assertSee('Y');
});

test('village C panel toggles hero resources for celebration shortages', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => 'celebration-hero',
        'name' => 'Celebration Village',
        'is_active' => true,
    ]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'celebration_use_hero_resources' => false,
    ]);

    Livewire::test(VillageRow::class, ['villageId' => $village->id])
        ->assertSee('Hero resources if short')
        ->call('toggleVillageCelebrationHeroResources', $village->id)
        ->assertHasNoErrors();

    expect($village->settings->fresh()->celebration_use_hero_resources)->toBeTrue();
});

test('village transfer modal queues a merchant snapshot refresh manually', function () {
    Queue::fake();

    $account = Account::factory()->create();
    $sourceVillage = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'x' => 60,
        'y' => 19,
        'is_active' => true,
    ]);
    $sourceVillage->buildings()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'current_level' => 1,
    ]);

    Livewire::test(Index::class)
        ->call('openMarketplaceTransferModal', $sourceVillage->id)
        ->assertDontSeeHtml('wire:poll.3s="refreshMarketplaceTransferCapacityView"')
        ->assertSee('Merchant capacity unavailable')
        ->assertSee('Refresh to check the available merchants.')
        ->call('refreshMarketplaceSnapshot')
        ->assertSeeHtml('wire:poll.3s="refreshMarketplaceTransferCapacityView"');

    Queue::assertPushed(RefreshVillageMarketplaceSnapshotJob::class, function (RefreshVillageMarketplaceSnapshotJob $job) use ($account, $sourceVillage) {
        return $job->accountId === $account->id
            && $job->villageId === $sourceVillage->id;
    });
});

test('village transfer modal keeps old merchant capacity when opened', function () {
    Queue::fake();

    $account = Account::factory()->create();
    $sourceVillage = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'x' => 60,
        'y' => 19,
        'is_active' => true,
    ]);
    $sourceVillage->buildings()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'current_level' => 10,
    ]);
    $sourceVillage->resourceState()->create([
        'available_merchants' => 2,
        'merchant_capacity' => 500,
        'server_reported_at' => now(),
    ]);
    $sourceVillage->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);
    $account->villages()->create([
        'travian_village_id' => '26000',
        'name' => 'CR7',
        'x' => 9,
        'y' => 59,
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('openMarketplaceTransferModal', $sourceVillage->id)
        ->assertSee('2 merchant(s)')
        ->assertSee('500 each');

    Queue::assertNotPushed(RefreshVillageMarketplaceSnapshotJob::class);

    expect($sourceVillage->resourceState()->first()?->available_merchants)->toBe(2);
});

test('village demolition modal requires main building level ten', function () {
    Queue::fake();

    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'x' => 60,
        'y' => 19,
        'is_active' => true,
    ]);
    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 9,
    ]);
    $village->buildings()->create([
        'slot_id' => 21,
        'building_gid' => 23,
        'building_type' => 'المخبأ',
        'current_level' => 7,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageDemolitionModal', $village->id)
        ->assertSee('Level 9')
        ->assertSee('level 10 is required')
        ->call('queueVillageBuildingDemolition');

    Queue::assertNotPushed(DemolishVillageBuildingJob::class);
});

test('village demolition modal queues refresh demolish and cancel jobs', function () {
    Queue::fake();

    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'x' => 60,
        'y' => 19,
        'is_active' => true,
    ]);
    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 10,
    ]);
    $village->buildings()->create([
        'slot_id' => 21,
        'building_gid' => 23,
        'building_type' => 'المخبأ',
        'current_level' => 7,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'demolition_entries' => [
            'main_building_level' => 10,
            'available_buildings' => [
                ['slot_id' => 21, 'name' => 'المخبأ', 'level' => 7],
            ],
            'active' => [
                'name' => 'المخبأ',
                'target_level' => 6,
                'remaining_seconds' => 512,
                'remaining_label' => '0:08:32',
                'finish_label' => '20:30',
                'cancel_uri' => '/build.php?gid=15&del=932338',
                'recorded_at' => now()->toIso8601String(),
            ],
            'recorded_at' => now()->toIso8601String(),
        ],
        'server_reported_at' => now(),
    ]);

    Livewire::test(Index::class)
        ->call('openVillageDemolitionModal', $village->id)
        ->assertSee('المخبأ')
        ->assertSee('Active demolition')
        ->set('demolitionSelectedSlotId', 21)
        ->call('refreshVillageDemolitionSnapshot')
        ->call('queueVillageBuildingDemolition')
        ->assertSet('showVillageDemolitionModal', false)
        ->call('openVillageDemolitionModal', $village->id)
        ->call('queueCancelVillageDemolition')
        ->assertSet('showVillageDemolitionModal', false);

    Queue::assertPushed(RefreshVillageDemolitionSnapshotJob::class, fn (RefreshVillageDemolitionSnapshotJob $job): bool => $job->villageId === $village->id);
    Queue::assertPushed(DemolishVillageBuildingJob::class, fn (DemolishVillageBuildingJob $job): bool => $job->villageId === $village->id && $job->slotId === 21);
    Queue::assertPushed(CancelVillageDemolitionJob::class, fn (CancelVillageDemolitionJob $job): bool => $job->villageId === $village->id && $job->cancelUri === '/build.php?gid=15&del=932338');
});

test('village transfer modal saves trade settings', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'x' => 60,
        'y' => 19,
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'send_enabled' => true,
        'support_enabled' => true,
        'supply_negative_crop_enabled' => true,
        'send_min_resource_percentage' => 30,
        'send_reserve_resource_percentage' => 10,
    ]);

    Livewire::test(Index::class)
        ->call('openMarketplaceTransferModal', $village->id)
        ->call('setMarketplaceTransferTab', 'settings')
        ->set('villageSendResourcesDraft', false)
        ->set('villageSupplyResourcesDraft', true)
        ->set('villageSupplyNegativeCropDraft', false)
        ->set('villageSendMinResourcePercentageDraft', 45)
        ->set('villageSendReserveResourcePercentageDraft', 25)
        ->call('saveMarketplaceTradeSettings')
        ->assertSet('marketplaceTransferTab', 'settings');

    $settings = $village->fresh()->settings;

    expect($settings?->send_enabled)->toBeFalse()
        ->and($settings?->support_enabled)->toBeTrue()
        ->and($settings?->supply_negative_crop_enabled)->toBeFalse()
        ->and($settings?->send_min_resource_percentage)->toBe(45)
        ->and($settings?->send_reserve_resource_percentage)->toBe(25)
        ->and(ActivityLog::query()
            ->where('village_id', $village->id)
            ->where('message', 'Village trade settings updated from TR panel.')
            ->exists())->toBeTrue();
});

test('elapsed village timer queues one quiet village sync', function () {
    Queue::fake();

    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23379',
        'name' => 'قرية Timer',
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('queueVillageTimerSync', $village->id)
        ->call('queueVillageTimerSync', $village->id);

    Queue::assertPushed(SyncTravianAccountJob::class, function (SyncTravianAccountJob $job) use ($account, $village) {
        return $job->accountId === $account->id
            && $job->villageId === $village->id
            && $job->ignoreConnectionBackoff === true
            && $job->useReloadAuto === true;
    });
    Queue::assertPushed(SyncTravianAccountJob::class, 1);
    expect(ActivityLog::query()
        ->where('village_id', $village->id)
        ->where('message', 'Village timer elapsed; sync queued automatically.')
        ->count())->toBe(1);
});

test('elapsed village timer does not queue sync for a paused account', function () {
    Queue::fake();

    $account = Account::factory()->create([
        'is_active' => false,
        'status' => AccountStatus::Paused,
    ]);
    $village = $account->villages()->create([
        'travian_village_id' => '23380',
        'name' => 'قرية Paused Account',
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('queueVillageTimerSync', $village->id);

    Queue::assertNothingPushed();
    expect(ActivityLog::query()
        ->where('village_id', $village->id)
        ->where('message', 'Village timer elapsed; sync queued automatically.')
        ->exists())->toBeFalse();
    expect($account->fresh()->status)->toBe(AccountStatus::Paused);
});

test('elapsed village timer does not queue sync for a paused village', function () {
    Queue::fake();

    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23380',
        'name' => 'قرية Paused Village',
        'is_active' => false,
    ]);

    Livewire::test(Index::class)
        ->call('queueVillageTimerSync', $village->id);

    Queue::assertNothingPushed();
    expect(ActivityLog::query()
        ->where('village_id', $village->id)
        ->where('message', 'Village timer elapsed; sync queued automatically.')
        ->exists())->toBeFalse();
    expect($account->fresh()->status)->not->toBe(AccountStatus::Syncing);
});

test('account update queues a manual sync even during connection cooldown', function () {
    Queue::fake();

    $account = Account::factory()->create([
        'status' => 'connection_issue',
        'connection_retry_after' => now()->addMinutes(5),
    ]);

    Livewire::test(Index::class)
        ->call('requestAccountSync', $account->id);

    expect($account->fresh()->status)->toBe(AccountStatus::Syncing);

    Queue::assertPushed(SyncTravianAccountJob::class, function (SyncTravianAccountJob $job) use ($account) {
        return $job->accountId === $account->id
            && $job->villageId === null
            && $job->ignoreConnectionBackoff === true;
    });
});

test('account row never mutates the dashboard revision reactive prop while requesting sync', function () {
    Queue::fake();
    $account = Account::factory()->create();

    Livewire::test(AccountRow::class, ['accountId' => $account->id, 'dashboardRevision' => 'parent-owned-revision'])
        ->call('requestAccountSync', $account->id)
        ->assertSet('dashboardRevision', 'parent-owned-revision');

    Queue::assertPushed(SyncTravianAccountJob::class);
});

test('open child modal pauses dashboard refresh without rendering away its selected tab', function () {
    Queue::fake();
    $account = Account::factory()->create();
    $village = $account->villages()->create(['travian_village_id' => '23378', 'name' => 'Stable modal', 'is_active' => true]);
    $village->settings()->create(['field_priority' => VillageSetting::defaultFieldPriority()]);

    Livewire::test(Index::class)
        ->call('updateDashboardModalVisibility', true)
        ->assertSet('dashboardChildModalOpen', true)
        ->call('markDashboardChanged')
        ->assertSet('dashboardChildModalOpen', true);

    Livewire::test(VillageSettingsModal::class)
        ->call('openVillageTroopOrders', $village->id)
        ->assertSet('showVillageBuildPlanModal', true)
        ->assertSet('villageSettingsTab', 'troops')
        ->call('$refresh')
        ->assertSet('showVillageBuildPlanModal', true)
        ->assertSet('villageSettingsTab', 'troops');
});

test('account update ignores repeated clicks while sync work is pending', function () {
    Queue::fake();

    $account = Account::factory()->create();

    Livewire::test(Index::class)
        ->call('requestAccountSync', $account->id)
        ->call('requestAccountSync', $account->id);

    Queue::assertPushed(SyncTravianAccountJob::class, 1);

    expect(ActivityLog::query()
        ->where('account_id', $account->id)
        ->where('message', 'Sync requested and queued from dashboard.')
        ->count())->toBe(1);
});

test('account update is not queued while account is paused', function () {
    Queue::fake();

    $account = Account::factory()->create([
        'is_active' => false,
        'status' => AccountStatus::Paused,
    ]);

    Livewire::test(Index::class)
        ->call('requestAccountSync', $account->id);

    Queue::assertNothingPushed();
    expect(ActivityLog::query()->where('account_id', $account->id)->exists())->toBeFalse();
    expect($account->fresh()->status)->toBe(AccountStatus::Paused);
});

test('dashboard displays paused for inactive accounts even if an old syncing status is stored', function () {
    $account = Account::factory()->create([
        'username' => 'stale-sync',
        'is_active' => false,
        'status' => AccountStatus::Syncing,
    ]);

    Livewire::test(Index::class)
        ->assertSee('stale-sync')
        ->assertSee('paused')
        ->assertDontSee('syncing');

    expect($account->fresh()->status)->toBe(AccountStatus::Syncing);
});

test('bulk import archives managed accounts removed from the latest snapshot', function () {
    Queue::fake();

    Livewire::test(Index::class)
        ->set('bulkImportDraft', implode("\n", [
            '!https://ts7.x1.arabics.travian.com/!marshal!12345678!127.0.0.1!8080!Mozilla/5.0',
            '!https://ts7.x1.arabics.travian.com/!strategist!12345678!!!Mozilla/5.0',
        ]))
        ->call('importAccounts');

    Livewire::test(Index::class)
        ->set('bulkImportDraft', '!https://ts7.x1.arabics.travian.com/!marshal!12345678!127.0.0.1!8080!Mozilla/5.0')
        ->call('importAccounts');

    $archivedAccount = Account::query()->where('username', 'strategist')->first();

    expect($archivedAccount)->not->toBeNull();
    expect($archivedAccount?->is_archived)->toBeTrue();
    expect($archivedAccount?->is_active)->toBeFalse();
    expect(Account::query()->where('is_archived', false)->pluck('username')->all())->toBe(['marshal']);
});

test('dashboard keeps accounts in the same order as the latest bulk import', function () {
    Queue::fake();

    Livewire::test(Index::class)
        ->set('bulkImportDraft', implode("\n", [
            '!https://ts7.x1.arabics.travian.com/!third!12345678!!!Mozilla/5.0',
            '!https://ts7.x1.arabics.travian.com/!first!12345678!!!Mozilla/5.0',
            '!https://ts7.x1.arabics.travian.com/!second!12345678!!!Mozilla/5.0',
        ]))
        ->call('importAccounts');

    Account::query()->where('username', 'first')->update(['last_sync_at' => now()]);

    expect(Account::query()->orderBy('import_position')->pluck('username')->all())->toBe([
        'third',
        'first',
        'second',
    ]);

    Livewire::test(Index::class)
        ->assertSeeInOrder(['third', 'first', 'second']);
});

test('dashboard toggles the global automation switch', function () {
    expect(SystemSetting::automationEnabled())->toBeTrue();

    Livewire::test(Index::class)
        ->call('toggleAutomation')
        ->assertSee('Paused')
        ->assertSee('Resume')
        ->assertSee('bg-amber-50/95');

    expect(SystemSetting::automationEnabled())->toBeFalse();

    Livewire::test(Index::class)
        ->call('toggleAutomation')
        ->assertSee('Enabled')
        ->assertSee('Pause');

    expect(SystemSetting::automationEnabled())->toBeTrue();
});

test('dashboard separates automation intent from runtime process health', function () {
    Livewire::test(Index::class)
        ->assertSee('Enabled')
        ->assertSee('Services unavailable')
        ->assertDontSee('Running');

    SystemSetting::markRuntimeHeartbeat('queue_worker', now());
    SystemSetting::markRuntimeHeartbeat('scheduler', now());

    Livewire::test(Index::class)
        ->assertSee('Enabled')
        ->assertSee('Services ready');
});

test('dashboard saves the global fallback user agent setting', function () {
    Livewire::test(Index::class)
        ->set('defaultUserAgent', 'Mozilla/5.0 Test Global Agent')
        ->set('globalFieldPriorityDraft', [
            'wood' => 2,
            'clay' => 1,
            'iron' => 3,
            'crop' => 4,
        ])
        ->set('globalPrioritizeCropFieldsWhenNegativeDraft', false)
        ->set('globalFieldLevelCapDraft', 12)
        ->set('globalTradeMaxDurationMinutesDraft', 120)
        ->call('saveProgramSettings')
        ->assertHasNoErrors();

    expect(SystemSetting::defaultUserAgent())->toBe('Mozilla/5.0 Test Global Agent');
    expect(SystemSetting::constructionDefaults()['field_priority'])->toBe([
        'wood' => 2,
        'clay' => 1,
        'iron' => 3,
        'crop' => 4,
    ]);
    expect(SystemSetting::constructionDefaults()['prioritize_crop_fields_when_negative'])->toBeFalse();
    expect(SystemSetting::constructionDefaults()['field_level_cap'])->toBe(12);
    expect(SystemSetting::tradeDefaults())->toBe([
        'max_duration_seconds' => 7200,
    ]);
});

test('dashboard shows the inherited global fallback user agent for accounts without one', function () {
    SystemSetting::setDefaultUserAgent('Mozilla/5.0 Shared Agent');

    $account = Account::factory()->create([
        'username' => 'marshal',
        'user_agent' => null,
    ]);

    $account->settings()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Mozilla/5.0 Shared Agent');
});

test('dashboard saves per account user agent and hero settings', function () {
    $account = Account::factory()->create([
        'username' => 'hero-owner',
        'user_agent' => null,
    ]);
    $account->settings()->create();

    Livewire::test(AccountSettingsModal::class)
        ->call('openAccountSettingsModal', $account->id)
        ->assertSet('showAccountSettingsModal', true)
        ->call('setAccountSettingsTab', 'hero')
        ->assertSee('Hero automation mode')
        ->assertSee('Effective Program Hero settings')
        ->assertDontSee('Account Hero overrides')
        ->set('accountInheritUserAgentDraft', false)
        ->set('accountUserAgentDraft', 'Mozilla/5.0 Account Hero Agent')
        ->set('accountAcceptQuestsDraft', false)
        ->set('accountHeroUseGlobalSettingsDraft', false)
        ->assertSee('Account Hero overrides')
        ->set('accountHeroAdventuresEnabledDraft', true)
        ->set('accountHeroMinHealthDraft', 55)
        ->set('accountHeroReviveEnabledDraft', true)
        ->set('accountHeroAttributeUpgradeEnabledDraft', true)
        ->set('accountHeroAttributeWeightsDraft', [
            'power' => 1,
            'offBonus' => 2,
            'defBonus' => 3,
            'productionPoints' => 4,
        ])
        ->call('saveAccountSettings')
        ->assertHasNoErrors()
        ->assertSet('showAccountSettingsModal', false);

    $account->refresh();
    $settings = $account->settings()->first();

    expect($account->user_agent)->toBe('Mozilla/5.0 Account Hero Agent')
        ->and($settings?->accept_quests)->toBeFalse()
        ->and($settings?->hero_use_global_settings)->toBeFalse()
        ->and($settings?->hero_adventures_enabled)->toBeTrue()
        ->and($settings?->hero_min_health)->toBe(55)
        ->and($settings?->hero_revive_enabled)->toBeTrue()
        ->and($settings?->hero_attribute_upgrade_enabled)->toBeTrue()
        ->and($settings?->hero_attribute_weights)->toBe([
            'power' => 1,
            'offBonus' => 2,
            'defBonus' => 3,
            'productionPoints' => 4,
        ]);
});

test('account Hero tab shows the last known resource inventory without refreshing Travian automatically', function () {
    $account = Account::factory()->create(['username' => 'hero-inventory']);
    $account->settings()->create();
    $account->heroState()->create([
        'payload' => [
            'resource_inventory' => [
                'wood' => 1200,
                'clay' => 2300,
                'iron' => 3400,
                'crop' => 4500,
                'reported_at' => now()->subMinute()->toIso8601String(),
            ],
        ],
    ]);

    Livewire::test(AccountSettingsModal::class)
        ->call('openAccountSettingsModal', $account->id)
        ->call('setAccountSettingsTab', 'hero')
        ->assertSee('Hero resources')
        ->assertSee('1,200')
        ->assertSee('2,300')
        ->assertSee('3,400')
        ->assertSee('4,500')
        ->assertSee('Refresh Hero resources')
        ->assertSeeHtml('assets/res-icons/lumber_small.png')
        ->assertSeeHtml('assets/res-icons/crop_small.png')
        ->assertSeeHtml('role="tablist"')
        ->assertDontSee('Refresh resources')
        ->assertSee('Read only');
});

test('dashboard toggles village field, building, and hero resource automation flags', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('toggleVillageFieldsAutomation', $village->id)
        ->call('toggleVillageBuildingsAutomation', $village->id)
        ->call('toggleVillageHeroResources', $village->id);

    $village->refresh();

    expect($village->settings)->not->toBeNull();
    expect($village->settings?->field_priority)->toBe(VillageSetting::defaultFieldPriority());
    expect($village->settings?->pause_fields)->toBeTrue();
    expect($village->settings?->pause_buildings)->toBeTrue();
    expect($village->settings?->hero_resources_enabled)->toBeFalse();
});

test('dashboard toggles individual field and building slot automation', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $fieldSlot = $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 4,
    ]);
    $buildingSlot = $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 5,
    ]);
    $target = $village->buildingTargets()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'target_level' => 8,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    Livewire::test(Index::class)
        ->call('toggleVillageFieldSlotAutomation', $village->id, 1)
        ->call('toggleVillageBuildingSlotAutomation', $village->id, 26);

    expect($fieldSlot->fresh()?->automation_enabled)->toBeFalse();
    expect($buildingSlot->fresh()?->automation_enabled)->toBeFalse();
    expect($target->fresh()?->is_enabled)->toBeFalse();
});

test('dashboard stores schedule pin and hold preferences', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    Livewire::test(Index::class)
        ->call('toggleVillageSchedulePin', $village->id, 'field:1:5')
        ->call('toggleVillageScheduleHold', $village->id, 'field:1:5')
        ->call('toggleVillageSchedulePin', $village->id, 'building-target:26:15');

    expect($village->fresh()->settings?->construction_schedule)->toBe([
        'pinned' => ['building-target:26:15', 'field:1:5'],
        'held' => ['field:1:5'],
    ]);

    Livewire::test(Index::class)
        ->call('toggleVillageSchedulePin', $village->id, 'field:1:5')
        ->call('toggleVillageScheduleHold', $village->id, 'field:1:5');

    expect($village->fresh()->settings?->construction_schedule)->toBe([
        'pinned' => ['building-target:26:15'],
        'held' => [],
    ]);
});

test('dashboard opens the village settings modal with existing slot data', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => [
            'wood' => 4,
            'clay' => 2,
            'iron' => 1,
            'crop' => 3,
        ],
        'pause_fields' => true,
        'pause_buildings' => false,
        'celebration_enabled' => true,
        'celebration_type' => 'great',
        'celebration_min_culture_points' => 300,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 8,
    ]);
    $village->buildings()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'current_level' => 10,
    ]);
    $village->buildings()->create([
        'slot_id' => 30,
        'building_gid' => 10,
        'building_type' => 'المخزن',
        'current_level' => 1,
    ]);
    $village->buildings()->create([
        'slot_id' => 31,
        'building_gid' => 11,
        'building_type' => 'مخزن الحبوب',
        'current_level' => 1,
    ]);

    $village->buildingTargets()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'target_level' => 12,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    Livewire::test(VillageSettingsModal::class)
        ->call('openVillageSettingsModal', $village->id)
        ->assertSet('showVillageBuildPlanModal', true)
        ->assertSet('editingVillageId', $village->id)
        ->assertSet('editingVillageTribeLabel', 'Roman')
        ->assertSeeHtml('role="tablist"')
        ->assertSee('Automatic upgrades')
        ->assertSee('Field priority mode')
        ->assertSee('Hero resources')
        ->assertSee('Use Hero Resources')
        ->assertSet('villageFieldsAutomationDraft', false)
        ->assertSet('villageBuildingsAutomationDraft', true)
        ->assertSet('villageInheritProgramPriorityDraft', true)
        ->assertSet('villageSendResourcesDraft', true)
        ->assertSet('villageSupplyResourcesDraft', true)
        ->assertSet('villageHeroResourcesDraft', true)
        ->assertSet('villageSupplyNegativeCropDraft', true)
        ->assertSet('villageCelebrationEnabledDraft', true)
        ->assertSet('villageCelebrationTypeDraft', 'great')
        ->assertSet('villageCelebrationMinimumCulturePointsDraft', 300)
        ->assertSet('villagePrioritizeCropFieldsWhenNegativeDraft', true)
        ->assertSet('villageFieldPriorityDraft.wood', 4)
        ->assertSet('villageBuildingPlanDraft.26.current_gid', 15)
        ->assertSet('villageBuildingPlanDraft.26.target_level', 12)
        ->call('setVillageSettingsTab', 'layouts')
        ->assertSee('Lv 8')
        ->assertSeeHtml('bg-emerald-500/10')
        ->assertSeeHtml('sticky top-0')
        ->assertSeeHtml('bg-slate-200/90')
        ->assertDontSee('locked')
        ->assertDontSeeHtml('wire:model.live="villageBuildingPlanDraft.26.building_gid"')
        ->assertSeeHtml('max="4"')
        ->call('setVillageSettingsTab', 'trading')
        ->assertSee('Trading policy')
        ->assertSee('Send resources')
        ->assertSee('Receive support')
        ->assertSee('Negative crop support')
        ->assertDontSee('Use Hero Resources');
});

test('dashboard saves village field priorities, automation toggles, and building targets from the settings modal', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 5,
    ]);
    $village->buildings()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'current_level' => 10,
    ]);
    $village->buildings()->create([
        'slot_id' => 30,
        'building_gid' => 10,
        'building_type' => 'المخزن',
        'current_level' => 1,
    ]);
    $village->buildings()->create([
        'slot_id' => 31,
        'building_gid' => 11,
        'building_type' => 'مخزن الحبوب',
        'current_level' => 1,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageFieldsAutomationDraft', false)
        ->set('villageBuildingsAutomationDraft', true)
        ->set('villageInheritProgramPriorityDraft', false)
        ->set('villageSendResourcesDraft', false)
        ->set('villageSupplyResourcesDraft', false)
        ->set('villageHeroResourcesDraft', false)
        ->set('villageSupplyNegativeCropDraft', false)
        ->set('villageTradeMaxDurationMinutesDraft', 180)
        ->set('villageCelebrationEnabledDraft', true)
        ->set('villageCelebrationTypeDraft', 'great')
        ->set('villageCelebrationMinimumCulturePointsDraft', 300)
        ->set('villageCelebrationUseHeroResourcesDraft', true)
        ->set('villagePrioritizeCropFieldsWhenNegativeDraft', false)
        ->set('villageFieldLevelCapModeDraft', 'custom')
        ->set('villageFieldLevelCapDraft', 15)
        ->set('villageFieldPriorityDraft', [
            'wood' => 4,
            'clay' => 1,
            'iron' => 2,
            'crop' => 3,
        ])
        ->set('villageBuildingPlanDraft.26.building_gid', 15)
        ->set('villageBuildingPlanDraft.26.target_level', 7)
        ->set('villageBuildingPlanDraft.26.priority', 1)
        ->set('villageBuildingPlanDraft.26.is_enabled', true)
        ->set('villageBuildingPlanDraft.21.building_gid', 17)
        ->set('villageBuildingPlanDraft.21.target_level', 5)
        ->set('villageBuildingPlanDraft.21.priority', 2)
        ->set('villageBuildingPlanDraft.21.is_enabled', true)
        ->call('saveVillageSettings')
        ->assertHasNoErrors()
        ->assertSet('showVillageBuildPlanModal', true);

    $savedSettings = $village->fresh()->settings;

    expect($savedSettings?->field_priority)->toBe([
        'wood' => 4,
        'clay' => 1,
        'iron' => 2,
        'crop' => 3,
    ]);
    expect($savedSettings?->pause_fields)->toBeTrue();
    expect($savedSettings?->pause_buildings)->toBeFalse();
    expect($savedSettings?->inherit_from_account)->toBeFalse();
    expect($savedSettings?->send_enabled)->toBeFalse();
    expect($savedSettings?->support_enabled)->toBeFalse();
    expect($savedSettings?->hero_resources_enabled)->toBeFalse();
    expect($savedSettings?->supply_negative_crop_enabled)->toBeFalse();
    expect($savedSettings?->trade_max_duration_seconds)->toBe(3 * 60 * 60);
    expect($savedSettings?->celebration_enabled)->toBeTrue();
    expect($savedSettings?->celebration_type?->value)->toBe('great');
    expect($savedSettings?->celebration_min_culture_points)->toBe(300);
    expect($savedSettings?->celebration_use_hero_resources)->toBeTrue();
    expect($savedSettings?->prioritize_crop_fields_when_negative)->toBeFalse();
    expect($savedSettings?->field_level_cap_mode)->toBe('custom');
    expect($savedSettings?->field_level_cap)->toBe(10);

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 26)
            ->first()
            ?->only(['building_gid', 'target_level', 'priority', 'is_enabled'])
    )->toBe([
        'building_gid' => 15,
        'target_level' => 7,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 21)
            ->first()
            ?->only(['building_gid', 'target_level', 'priority', 'is_enabled'])
    )->toBe([
        'building_gid' => 17,
        'target_level' => 5,
        'priority' => 2,
        'is_enabled' => true,
    ]);
});

test('village transfer modal clamps resources above known merchant capacity', function () {
    Queue::fake();

    $account = Account::factory()->create();
    $sourceVillage = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'x' => 60,
        'y' => 19,
        'is_active' => true,
    ]);
    $sourceVillage->resourceState()->create([
        'wood' => 1200,
        'clay' => 800,
        'iron' => 800,
        'crop' => 800,
        'available_merchants' => 2,
        'merchant_capacity' => 500,
    ]);
    $sourceVillage->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);
    $account->villages()->create([
        'travian_village_id' => '26000',
        'name' => 'CR7',
        'x' => 9,
        'y' => 59,
        'is_active' => true,
    ]);

    Livewire::test(Index::class)
        ->call('openMarketplaceTransferModal', $sourceVillage->id)
        ->assertSee('2 merchant(s)')
        ->assertSee('500 each')
        ->assertSee('Can send 1000')
        ->set('marketplaceWoodDraft', 1000)
        ->set('marketplaceClayDraft', 500)
        ->assertSet('marketplaceWoodDraft', 1000)
        ->assertSet('marketplaceClayDraft', 0)
        ->call('queueManualMarketplaceTransfer')
        ->assertSet('showMarketplaceTransferModal', false);

    Queue::assertPushed(SendManualMarketplaceTransferJob::class, function (SendManualMarketplaceTransferJob $job): bool {
        return $job->resources === [
            'wood' => 1000,
            'clay' => 0,
            'iron' => 0,
            'crop' => 0,
        ];
    });

    expect(ActivityLog::query()
        ->where('village_id', $sourceVillage->id)
        ->where('message', 'Manual marketplace transfer queued from dashboard.')
        ->exists())->toBeTrue();
});

test('dashboard blocks village celebrations until a town hall exists', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23379',
        'name' => 'قرية بدون بلدية',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageCelebrationEnabledDraft', true)
        ->assertSet('villageCelebrationReadinessMessage', 'Cannot enable celebrations yet: this village does not have a Town Hall.')
        ->call('saveVillageSettings')
        ->assertHasErrors('villageCelebrationEnabledDraft');
});

test('dashboard defaults newly enabled celebrations to small celebrations', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23381',
        'name' => 'قرية احتفالات',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'current_level' => 10,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageCelebrationEnabledDraft', true)
        ->assertSet('villageCelebrationTypeDraft', 'small');
});

test('dashboard blocks great celebrations until town hall level ten', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23380',
        'name' => 'قرية بلدية صغيرة',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'current_level' => 5,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageCelebrationEnabledDraft', true)
        ->set('villageCelebrationTypeDraft', 'great')
        ->assertSet('villageCelebrationReadinessMessage', 'Cannot use Great celebrations yet: Town Hall is level 5, and level 10 is required.')
        ->call('saveVillageSettings')
        ->assertHasErrors('villageCelebrationTypeDraft');
});

test('dashboard allows duplicate field priorities in village settings', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23382',
        'name' => 'قرية Balanced',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageFieldPriorityDraft', [
            'wood' => 1,
            'clay' => 1,
            'iron' => 2,
            'crop' => 2,
        ])
        ->call('saveVillageSettings')
        ->assertHasNoErrors();

    expect($village->fresh()->settings?->field_priority)->toBe([
        'wood' => 1,
        'clay' => 1,
        'iron' => 2,
        'crop' => 2,
    ]);
});

test('dashboard rejects changing an occupied slot to a different building gid', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 5,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageBuildingPlanDraft.26.building_gid', 10)
        ->set('villageBuildingPlanDraft.26.target_level', 6)
        ->call('saveVillageSettings')
        ->assertHasErrors('villageBuildingPlanDraft.26.building_gid');

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 26)
            ->first()
            ?->only(['building_gid', 'target_level', 'priority', 'is_enabled'])
    )->toBe([
        'building_gid' => 15,
        'target_level' => 14,
        'priority' => 1,
        'is_enabled' => true,
    ]);
});

test('dashboard allows keeping the current building level as the final target', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 5,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageBuildingPlanDraft.26.building_gid', 15)
        ->set('villageBuildingPlanDraft.26.target_level', 5)
        ->call('saveVillageSettings')
        ->assertHasNoErrors();

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 26)
            ->first()
            ?->target_level
    )->toBe(5);
});

test('dashboard clamps resource bonus building target levels to five', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);
    $village->buildings()->create([
        'slot_id' => 21,
        'building_gid' => 0,
        'building_type' => null,
        'current_level' => 0,
    ]);
    $village->buildings()->create([
        'slot_id' => 4,
        'building_gid' => 4,
        'building_type' => 'حقل القمح',
        'current_level' => 5,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageBuildingPlanDraft.21.building_gid', 8)
        ->set('villageBuildingPlanDraft.21.target_level', 12)
        ->set('villageBuildingPlanDraft.21.priority', 1)
        ->set('villageBuildingPlanDraft.21.is_enabled', true)
        ->call('saveVillageSettings')
        ->assertHasNoErrors();

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 21)
            ->first()
            ?->target_level
    )->toBe(5);
});

test('dashboard rejects unavailable building targets before their requirements are met', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);
    $village->buildings()->create([
        'slot_id' => 25,
        'building_gid' => 0,
        'building_type' => null,
        'current_level' => 0,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageBuildingPlanDraft.25.building_gid', 20)
        ->set('villageBuildingPlanDraft.25.target_level', 2)
        ->set('villageBuildingPlanDraft.25.priority', 1)
        ->call('saveVillageSettings')
        ->assertHasErrors('villageBuildingPlanDraft.25.building_gid');
});

test('dashboard shows unavailable layout building options with disabled reasons', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);
    $village->buildings()->create([
        'slot_id' => 25,
        'building_gid' => 0,
        'building_type' => null,
        'current_level' => 0,
    ]);

    $component = Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->call('setVillageSettingsTab', 'layouts')
        ->assertSee('الإسطبل')
        ->assertSee('needs');

    $slotOptions = collect($component->get('slotBuildingOptions')[25] ?? []);

    expect($slotOptions->firstWhere('gid', 20))
        ->toMatchArray([
            'gid' => 20,
            'selectable' => false,
        ]);
    expect($slotOptions->firstWhere('gid', 20)['unavailable_reason'] ?? '')
        ->toContain('أفران صهر الحديد Lv 3 required');
    expect($slotOptions->firstWhere('gid', 38))
        ->toMatchArray([
            'gid' => 38,
            'selectable' => false,
        ]);
});

test('dashboard allows another duplicate-limited building option once any existing copy reaches max level', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);
    $village->buildings()->create([
        'slot_id' => 19,
        'building_gid' => 10,
        'building_type' => 'المخزن',
        'current_level' => 20,
    ]);
    $village->buildings()->create([
        'slot_id' => 20,
        'building_gid' => 23,
        'building_type' => 'المخبأ',
        'current_level' => 10,
    ]);
    $village->buildings()->create([
        'slot_id' => 21,
        'building_gid' => 10,
        'building_type' => 'المخزن',
        'current_level' => 8,
    ]);
    $village->buildings()->create([
        'slot_id' => 22,
        'building_gid' => 23,
        'building_type' => 'المخبأ',
        'current_level' => 6,
    ]);
    $village->buildings()->create([
        'slot_id' => 24,
        'building_gid' => 11,
        'building_type' => 'مخزن الحبوب',
        'current_level' => 20,
    ]);
    $village->buildings()->create([
        'slot_id' => 27,
        'building_gid' => 11,
        'building_type' => 'مخزن الحبوب',
        'current_level' => 8,
    ]);
    $village->buildings()->create([
        'slot_id' => 25,
        'building_gid' => 0,
        'building_type' => null,
        'current_level' => 0,
    ]);

    $component = Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->call('setVillageSettingsTab', 'layouts');

    expect(collect($component->get('slotBuildingOptions')[25] ?? [])->firstWhere('gid', 10))
        ->toMatchArray([
            'gid' => 10,
            'selectable' => true,
        ]);
    expect(collect($component->get('slotBuildingOptions')[25] ?? [])->firstWhere('gid', 11))
        ->toMatchArray([
            'gid' => 11,
            'selectable' => true,
        ]);
    expect(collect($component->get('slotBuildingOptions')[25] ?? [])->firstWhere('gid', 23))
        ->toMatchArray([
            'gid' => 23,
            'selectable' => true,
        ]);
});

test('dashboard defaults observed resource bonus buildings and marks completed buildings', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);
    $village->buildings()->create([
        'slot_id' => 21,
        'building_gid' => 8,
        'building_type' => 'المطاحن',
        'current_level' => 2,
    ]);
    $village->buildings()->create([
        'slot_id' => 22,
        'building_gid' => 23,
        'building_type' => 'المخبأ',
        'current_level' => 10,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->assertSet('villageBuildingPlanDraft.21.target_level', 5)
        ->assertSet('villageBuildingPlanDraft.21.priority', 1)
        ->assertSet('villageBuildingPlanDraft.22.current_is_maxed', true)
        ->call('setVillageSettingsTab', 'layouts')
        ->assertSee('max')
        ->call('saveVillageSettings')
        ->assertHasNoErrors();

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 21)
            ->first()
            ?->only(['building_gid', 'target_level', 'priority', 'is_enabled'])
    )->toBe([
        'building_gid' => 8,
        'target_level' => 5,
        'priority' => 1,
        'is_enabled' => true,
    ]);
});

test('dashboard ignores stale completed building targets while saving another slot', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 19,
        'building_gid' => 10,
        'building_type' => 'المخزن',
        'current_level' => 9,
    ]);
    $village->buildings()->create([
        'slot_id' => 20,
        'building_gid' => 23,
        'building_type' => 'المخبأ',
        'current_level' => 3,
    ]);

    $village->buildingTargets()->create([
        'slot_id' => 19,
        'building_gid' => 10,
        'building_type' => 'المخزن',
        'target_level' => 6,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    Livewire::test(Index::class)
        ->call('openVillageSettingsModal', $village->id)
        ->set('villageBuildingPlanDraft.20.building_gid', 23)
        ->set('villageBuildingPlanDraft.20.target_level', 7)
        ->set('villageBuildingPlanDraft.20.priority', 2)
        ->set('villageBuildingPlanDraft.20.is_enabled', true)
        ->call('saveVillageSettings')
        ->assertHasNoErrors();

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 19)
            ->exists()
    )->toBeFalse();

    expect(
        VillageBuildingTarget::query()
            ->where('village_id', $village->id)
            ->where('slot_id', 20)
            ->first()
            ?->only(['building_gid', 'target_level', 'priority', 'is_enabled'])
    )->toBe([
        'building_gid' => 23,
        'target_level' => 7,
        'priority' => 2,
        'is_enabled' => true,
    ]);
});

test('dashboard shows construction countdown and finish time in the village row and activity log', function () {
    $now = now()->startOfSecond();
    Carbon::setTestNow($now);

    $account = Account::factory()->create([
        'username' => 'marshal',
    ]);

    $account->settings()->create();

    $village = $account->villages()->create([
        'travian_village_id' => '23390',
        'name' => 'قرية Timer',
        'x' => 9,
        'y' => 60,
        'population' => 89,
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
        'troop_training_enabled' => true,
        'celebration_enabled' => true,
    ]);

    $village->resourceState()->create([
        'wood' => 1800,
        'clay' => 1700,
        'iron' => 1600,
        'crop' => 1500,
        'wood_production' => 140,
        'clay_production' => 120,
        'iron_production' => 104,
        'crop_production' => 37,
        'warehouse_capacity' => 4000,
        'granary_capacity' => 1700,
        'simulated_at' => $now,
        'server_reported_at' => $now,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => array_fill(0, 11, 0),
        'incoming_reinforcement_count' => 1,
        'movement_entries' => [
            [
                'kind' => 'incoming_reinforcement',
                'label' => 'تعزيز',
                'remaining_seconds' => 0,
                'remaining_label' => '0:00:00',
            ],
        ],
        'construction_entries' => [
            [
                'building_name' => 'حفرة الطين',
                'target_level' => 4,
                'remaining_seconds' => 600,
                'remaining_label' => '0:10:00',
                'finish_label' => '22:25',
            ],
            [
                'building_name' => 'حفرة منتهية',
                'target_level' => 6,
                'remaining_seconds' => 0,
                'remaining_label' => '0:00:00',
                'finish_label' => '19:53',
            ],
        ],
        'server_reported_at' => $now,
    ]);

    ActivityLog::query()->create([
        'account_id' => $account->id,
        'village_id' => $village->id,
        'activity_type' => 'build',
        'status' => 'done',
        'payload' => [
            'building_name' => 'حفرة الطين',
            'target_level' => 4,
            'remaining_seconds' => 600,
            'remaining_label' => '0:10:00',
            'finish_label' => '22:25',
        ],
        'message' => 'Field upgrade order issued successfully.',
        'executed_at' => $now,
    ]);

    Livewire::test(VillageRow::class, [
        'villageId' => $village->id,
        'globalFieldPriority' => VillageSetting::defaultFieldPriority(),
        'globalFieldLevelCap' => 10,
        'globalPrioritizeCropFieldsWhenNegative' => true,
    ])
        ->assertSee('[0,0,0,0,0,0,0,0,0,0,0]')
        ->assertSee('Troops')
        ->assertSee('assets/troops-icons/hero.png')
        ->assertSee('assets/troops-icons/u1.png')
        ->assertSee('assets/res-icons/lumber_small.png')
        ->assertSee('assets/buildings-icons/type10_small.png')
        ->assertSee('assets/buildings-icons/type11_small.png')
        ->assertSee('4000')
        ->assertSee('1800')
        ->assertSee('+140/h')
        ->assertSee('#f88c1f')
        ->assertSee('T - Troop command panel: indigo means trainable units are ready; amber means orders are pending')
        ->assertSee('C - Celebrations: inspect and toggle town hall celebrations')
        ->assertSee('CP >= 200', false)
        ->assertDontSee('WH 4000')
        ->assertSee('0:10:00')
        ->assertSee('Ends 22:25')
        ->assertDontSee('حفرة منتهية')
        ->assertDontSee('تعزيز')
        ->assertDontSee('0:00:00');

    Carbon::setTestNow();
});

test('dashboard account row uses recent account activity when it is newer than the last sync time', function () {
    $now = now()->startOfSecond();
    Carbon::setTestNow($now);

    $account = Account::factory()->create([
        'username' => 'marshal',
        'last_sync_at' => $now->subHours(2),
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23392',
        'name' => 'CR7',
        'is_active' => true,
    ]);

    ActivityLog::query()->create([
        'account_id' => $account->id,
        'village_id' => $village->id,
        'activity_type' => 'build',
        'status' => 'done',
        'message' => 'Field upgrade order issued successfully.',
        'executed_at' => $now->subMinutes(8),
    ]);

    Livewire::test(Index::class)
        ->assertSee('8 minutes ago')
        ->assertDontSee('2 hours ago');

    Carbon::setTestNow();
});

test('dashboard keeps elapsed construction visible until the next sync confirms it is gone', function () {
    $now = now()->startOfSecond();
    Carbon::setTestNow($now);

    $account = Account::factory()->create([
        'username' => 'marshal',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23394',
        'name' => 'قرية بناء',
        'is_active' => true,
    ]);

    $village->runtimeState()->create([
        'troop_slots' => array_fill(0, 11, 0),
        'movement_entries' => [],
        'construction_entries' => [
            [
                'building_name' => 'الحطاب',
                'target_level' => 7,
                'remaining_seconds' => 30,
                'remaining_label' => '0:00:30',
                'finish_label' => '16:33',
            ],
        ],
        'server_reported_at' => $now->subMinute(),
    ]);

    Livewire::test(VillageRow::class, [
        'villageId' => $village->id,
        'globalFieldPriority' => VillageSetting::defaultFieldPriority(),
        'globalFieldLevelCap' => 10,
        'globalPrioritizeCropFieldsWhenNegative' => true,
    ])
        ->assertSee('الحطاب')
        ->assertSee('Lv 7')
        ->assertSeeHtml('wire:key="construction-timer-')
        ->assertSeeHtml('x-effect="tick($store.dashboardClock.now)"')
        ->assertSee('Sync due');

    Carbon::setTestNow();
});

test('dashboard schedule keeps pinned running building target visible for release', function () {
    $now = now()->startOfSecond();
    Carbon::setTestNow($now);

    $account = Account::factory()->create([
        'username' => 'marshal',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23395',
        'name' => 'قرية جدول',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
        'construction_schedule' => [
            'pinned' => ['building-target:33:22'],
            'held' => [],
        ],
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => array_fill(0, 11, 0),
        'movement_entries' => [],
        'construction_entries' => [
            [
                'building_name' => 'الأكاديمية',
                'target_level' => 5,
                'remaining_seconds' => 600,
                'remaining_label' => '0:10:00',
                'finish_label' => '19:47',
            ],
        ],
        'server_reported_at' => $now,
    ]);

    $village->buildings()->create([
        'slot_id' => 33,
        'building_gid' => 22,
        'building_type' => 'الأكاديمية',
        'current_level' => 4,
    ]);
    $village->buildingTargets()->create([
        'slot_id' => 33,
        'building_gid' => 22,
        'building_type' => 'الأكاديمية',
        'target_level' => 5,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    Livewire::test(VillageRow::class, [
        'villageId' => $village->id,
        'globalFieldPriority' => VillageSetting::defaultFieldPriority(),
        'globalFieldLevelCap' => 10,
        'globalPrioritizeCropFieldsWhenNegative' => true,
    ])
        ->assertSee('الأكاديمية')
        ->assertSeeHtml("schedule-entry-{$village->id}-building-target:33:22")
        ->assertSee('running');

    Carbon::setTestNow();
});

test('dashboard field schedule limits adjacent priority families to a one level gap', function () {
    $account = Account::factory()->create([
        'username' => 'marshal',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23396',
        'name' => 'قرية جدول الحقول',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'inherit_from_account' => false,
        'field_priority' => [
            'wood' => 1,
            'clay' => 2,
            'iron' => 3,
            'crop' => 4,
        ],
        'pause_fields' => false,
        'pause_buildings' => true,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => array_fill(0, 11, 0),
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 1, 'building_gid' => 1, 'building_type' => 'الحطاب', 'current_level' => 7],
        ['slot_id' => 4, 'building_gid' => 3, 'building_type' => 'منجم حديد', 'current_level' => 5],
        ['slot_id' => 5, 'building_gid' => 2, 'building_type' => 'حفرة الطين', 'current_level' => 6],
        ['slot_id' => 8, 'building_gid' => 4, 'building_type' => 'حقل القمح', 'current_level' => 5],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    Livewire::test(VillageRow::class, [
        'villageId' => $village->id,
        'globalFieldPriority' => VillageSetting::defaultFieldPriority(),
        'globalFieldLevelCap' => 10,
        'globalPrioritizeCropFieldsWhenNegative' => true,
    ])
        ->assertDontSeeHtml("schedule-entry-{$village->id}-field:5:7")
        ->assertSeeHtml("schedule-entry-{$village->id}-field:4:6")
        ->assertSeeHtml("schedule-entry-{$village->id}-field:8:6");
});

test('dashboard field schedule respects field level cap on non capital villages', function () {
    SystemSetting::setConstructionDefaults([
        'field_priority' => [
            'wood' => 1,
            'clay' => 2,
            'iron' => 3,
            'crop' => 4,
        ],
        'prioritize_crop_fields_when_negative' => true,
        'field_level_cap' => 20,
    ]);

    $account = Account::factory()->create([
        'username' => 'marshal',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23396',
        'name' => 'قرية غير عاصمة',
        'is_active' => true,
        'is_capital' => false,
    ]);

    $village->settings()->create([
        'inherit_from_account' => true,
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'field_level_cap_mode' => 'custom',
        'field_level_cap' => 20,
        'pause_fields' => false,
        'pause_buildings' => true,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => array_fill(0, 11, 0),
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 10,
    ]);

    Livewire::test(VillageRow::class, [
        'villageId' => $village->id,
        'globalFieldPriority' => VillageSetting::defaultFieldPriority(),
        'globalFieldLevelCap' => 20,
        'globalPrioritizeCropFieldsWhenNegative' => true,
    ])
        ->assertDontSeeHtml("schedule-entry-{$village->id}-field:1:11");
});

test('dashboard field schedule allows capital villages above level ten when capped higher', function () {
    SystemSetting::setConstructionDefaults([
        'field_priority' => [
            'wood' => 1,
            'clay' => 2,
            'iron' => 3,
            'crop' => 4,
        ],
        'prioritize_crop_fields_when_negative' => true,
        'field_level_cap' => 12,
    ]);

    $account = Account::factory()->create([
        'username' => 'marshal',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23396',
        'name' => 'العاصمة',
        'is_active' => true,
        'is_capital' => true,
    ]);

    $village->settings()->create([
        'inherit_from_account' => true,
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'field_level_cap_mode' => 'inherit',
        'pause_fields' => false,
        'pause_buildings' => true,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => array_fill(0, 11, 0),
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 10,
    ]);

    Livewire::test(VillageRow::class, [
        'villageId' => $village->id,
        'globalFieldPriority' => VillageSetting::defaultFieldPriority(),
        'globalFieldLevelCap' => 12,
        'globalPrioritizeCropFieldsWhenNegative' => true,
    ])
        ->assertSeeHtml("schedule-entry-{$village->id}-field:1:11");
});

test('dashboard account row ignores internal activity logs when showing the last account contact', function () {
    $now = now()->startOfSecond();
    Carbon::setTestNow($now);

    $account = Account::factory()->create([
        'username' => 'marshal',
        'last_sync_at' => $now->subHours(2),
    ]);

    ActivityLog::query()->create([
        'account_id' => $account->id,
        'activity_type' => 'manual',
        'status' => 'done',
        'message' => 'Account activated from dashboard.',
        'executed_at' => $now->subMinutes(3),
    ]);

    ActivityLog::query()->create([
        'account_id' => $account->id,
        'activity_type' => 'build',
        'status' => 'running',
        'message' => 'Checking local account automation plan.',
        'executed_at' => $now->subMinutes(1),
    ]);

    Livewire::test(AccountRow::class, [
        'accountId' => $account->id,
        'isExpanded' => false,
    ])
        ->assertSee('2 hours ago')
        ->assertDontSee('1 minute ago')
        ->assertDontSee('3 minutes ago');

    Carbon::setTestNow();
});

test('dashboard activity log displays the local PHP application timezone', function () {
    config()->set('app.timezone', date_default_timezone_get());
    $occurredAt = Carbon::parse('2026-06-18 10:00:00', 'UTC');

    $account = Account::factory()->create([
        'username' => 'marshal',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23393',
        'name' => 'Server Clock',
        'is_active' => true,
    ]);

    ActivityLog::query()->create([
        'account_id' => $account->id,
        'village_id' => $village->id,
        'activity_type' => 'sync',
        'status' => 'done',
        'message' => 'Village overview synced successfully from dorf1 and dorf2.',
        'executed_at' => $occurredAt,
    ]);

    $expectedLocalTime = $occurredAt
        ->copy()
        ->timezone(date_default_timezone_get())
        ->format('d/m/Y H:i:s');

    Livewire::test(Index::class)
        ->assertSee($expectedLocalTime);
});

test('dashboard activity log marks local and travian-backed log sources', function () {
    $account = Account::factory()->create([
        'username' => 'marshal',
    ]);

    ActivityLog::query()->create([
        'account_id' => $account->id,
        'activity_type' => ActivityType::Manual,
        'status' => ActivityLogStatus::Done,
        'message' => 'Local dashboard decision.',
        'executed_at' => now()->subSecond(),
    ]);

    ActivityLog::query()->create([
        'account_id' => $account->id,
        'activity_type' => ActivityType::Build,
        'status' => ActivityLogStatus::Done,
        'result' => [
            'effective_uri' => 'https://example.travian.test/build.php?id=19',
        ],
        'message' => 'Travian request completed.',
        'executed_at' => now(),
    ]);

    Livewire::test(Index::class)
        ->assertSee('APP')
        ->assertSee('TRAVIAN')
        ->assertSee('Local dashboard decision.')
        ->assertSee('Travian request completed.');
});

test('dashboard resizes the activity log drawer within bounds', function () {
    Livewire::test(Index::class)
        ->assertSet('activityLogHeight', 22)
        ->call('increaseActivityLogHeight')
        ->assertSet('activityLogHeight', 26)
        ->call('decreaseActivityLogHeight')
        ->call('decreaseActivityLogHeight')
        ->call('decreaseActivityLogHeight')
        ->assertSet('activityLogHeight', 16);
});

test('dashboard separates successful sync age from connection retry status', function () {
    $now = now()->startOfSecond();
    Carbon::setTestNow($now);

    $account = Account::factory()->create([
        'username' => 'marshal',
        'status' => 'connection_issue',
        'last_sync_at' => $now->subHours(21),
        'connection_failure_count' => 2,
        'connection_retry_after' => $now->addSeconds(30),
        'last_connection_error_at' => $now->subSeconds(4),
        'last_connection_error_message' => 'cURL error 28: Connection timed out.',
    ]);

    $account->settings()->create();

    Livewire::test(Index::class)
        ->assertSee('connection')
        ->assertSee('Synced 21 hours ago')
        ->assertSee('Retry 30 seconds from now')
        ->assertDontSee('Last connection issue')
        ->assertDontSee('Synced 4 seconds ago');

    Carbon::setTestNow();
});

test('dashboard poll queues due connection retries automatically', function () {
    Queue::fake();

    $account = Account::factory()->create([
        'status' => 'connection_issue',
        'connection_retry_after' => now()->subSecond(),
        'connection_failure_count' => 2,
    ]);

    Livewire::test(Index::class)
        ->call('refreshDashboardIfChanged');

    $account->refresh();

    expect($account->status)->toBe(AccountStatus::Syncing);

    Queue::assertPushed(SyncTravianAccountJob::class, function (SyncTravianAccountJob $job) use ($account) {
        return $job->accountId === $account->id
            && $job->ignoreConnectionBackoff === true;
    });

    expect(ActivityLog::query()->where('message', 'Connection retry window elapsed; sync queued automatically.')->exists())->toBeTrue();
});

test('dashboard styles outgoing attack movements with the attack icon palette', function () {
    $now = now()->startOfSecond();
    Carbon::setTestNow($now);

    $account = Account::factory()->create([
        'username' => 'marshal',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23391',
        'name' => 'قرية Movements',
        'x' => 9,
        'y' => 60,
        'population' => 120,
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => array_fill(0, 11, 0),
        'outgoing_movement_count' => 1,
        'movement_entries' => [
            [
                'kind' => 'outgoing',
                'label' => '1 هجوم',
                'remaining_seconds' => 300,
                'remaining_label' => '0:05:00',
            ],
        ],
        'construction_entries' => [],
        'server_reported_at' => $now,
    ]);

    Livewire::test(VillageRow::class, [
        'villageId' => $village->id,
        'globalFieldPriority' => VillageSetting::defaultFieldPriority(),
        'globalFieldLevelCap' => 10,
        'globalPrioritizeCropFieldsWhenNegative' => true,
    ])
        ->assertSee('assets/movements-icons/att2.gif')
        ->assertSee('#fff6b5')
        ->assertSeeHtml('wire:key="movement-timer-')
        ->assertSeeHtml('x-effect="tick($store.dashboardClock.now)"')
        ->assertSee('1 هجوم');

    Carbon::setTestNow();
});
