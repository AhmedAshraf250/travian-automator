<?php

use App\Application\Accounts\Construction\ExecuteVillageConstruction;
use App\Application\Accounts\Hero\UseHeroResourcesForConstruction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Sync\Data\ParsedConstructionQueueEntry;
use App\Application\Accounts\Sync\Data\ParsedDorf1Overview;
use App\Application\Accounts\Sync\Data\ParsedDorf2Overview;
use App\Application\Accounts\Sync\Data\ParsedVillageResourceState;
use App\Application\Accounts\Sync\Data\ParsedVillageRuntimeState;
use App\Application\Accounts\Sync\Data\ParsedVillageSlot;
use App\Application\Accounts\Sync\Data\ParsedVillageSummary;
use App\Application\Accounts\Sync\Parsers\Dorf1OverviewParser;
use App\Application\Accounts\Sync\Parsers\Dorf2OverviewParser;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\VillageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

function bindConstructionRefreshSnapshots(array $dorf1Overviews, array $dorf2Overviews): void
{
    app()->instance(Dorf1OverviewParser::class, new class($dorf1Overviews) extends Dorf1OverviewParser
    {
        /**
         * @param  list<ParsedDorf1Overview>  $overviews
         */
        public function __construct(protected array $overviews) {}

        public function parse(string $html): ParsedDorf1Overview
        {
            if (count($this->overviews) > 1) {
                /** @var ParsedDorf1Overview $overview */
                $overview = array_shift($this->overviews);

                return $overview;
            }

            return $this->overviews[0];
        }
    });

    app()->instance(Dorf2OverviewParser::class, new class($dorf2Overviews) extends Dorf2OverviewParser
    {
        /**
         * @param  list<ParsedDorf2Overview>  $overviews
         */
        public function __construct(protected array $overviews) {}

        public function parse(string $html): ParsedDorf2Overview
        {
            if (count($this->overviews) > 1) {
                /** @var ParsedDorf2Overview $overview */
                $overview = array_shift($this->overviews);

                return $overview;
            }

            return $this->overviews[0];
        }
    });
}

/**
 * @param  list<array{
 *     building_name: string,
 *     target_level: int,
 *     remaining_seconds: int,
 *     remaining_label: string|null,
 *     finish_label: string|null
 * }>  $constructionEntries
 * @param  list<array{
 *     slot_id: int,
 *     building_gid: int,
 *     building_name: string|null,
 *     current_level: int
 * }>  $fieldSlots
 */
function fakeDorf1Overview(
    string $villageId,
    string $villageName,
    int $tribeId,
    array $constructionEntries = [],
    array $fieldSlots = [],
): ParsedDorf1Overview {
    $queueEntries = array_map(
        static fn (array $entry): ParsedConstructionQueueEntry => new ParsedConstructionQueueEntry(
            buildingName: $entry['building_name'],
            targetLevel: $entry['target_level'],
            remainingSeconds: $entry['remaining_seconds'],
            remainingLabel: $entry['remaining_label'],
            finishLabel: $entry['finish_label'],
        ),
        $constructionEntries,
    );

    $parsedFieldSlots = array_map(
        static fn (array $slot): ParsedVillageSlot => new ParsedVillageSlot(
            slotId: $slot['slot_id'],
            buildingGid: $slot['building_gid'],
            buildingName: $slot['building_name'],
            currentLevel: $slot['current_level'],
            kind: 'field',
        ),
        $fieldSlots,
    );

    $summary = new ParsedVillageSummary(
        travianVillageId: $villageId,
        name: $villageName,
        x: 9,
        y: 60,
        population: 89,
        isActive: true,
        switchUri: '/dorf1.php?newdid='.$villageId,
    );

    return new ParsedDorf1Overview(
        activeVillage: $summary,
        resourceState: new ParsedVillageResourceState(
            wood: 1800,
            clay: 1700,
            iron: 1600,
            crop: 1500,
            woodProduction: 140,
            clayProduction: 120,
            ironProduction: 104,
            cropProduction: 37,
            freeCropProduction: 0,
            warehouseCapacity: 4000,
            granaryCapacity: 1700,
        ),
        runtimeState: new ParsedVillageRuntimeState(
            tribeId: $tribeId,
            troopSlots: array_fill(0, 11, 0),
            incomingAttackCount: 0,
            incomingReinforcementCount: 0,
            outgoingMovementCount: 0,
            movementEntries: [],
            constructionEntries: $queueEntries,
            heroStatus: null,
            heroRemainingSeconds: null,
        ),
        villages: [$summary],
        fieldSlots: $parsedFieldSlots,
        constructionQueue: $queueEntries,
    );
}

/**
 * @param  list<array{
 *     slot_id: int,
 *     building_gid: int,
 *     building_name: string|null,
 *     current_level: int,
 *     is_empty?: bool
 * }>  $buildingSlots
 */
function fakeDorf2Overview(array $buildingSlots = []): ParsedDorf2Overview
{
    return new ParsedDorf2Overview(
        buildingSlots: array_map(
            static fn (array $slot): ParsedVillageSlot => new ParsedVillageSlot(
                slotId: $slot['slot_id'],
                buildingGid: $slot['building_gid'],
                buildingName: $slot['building_name'],
                currentLevel: $slot['current_level'],
                kind: 'building',
                isEmpty: (bool) ($slot['is_empty'] ?? false),
            ),
            $buildingSlots,
        ),
    );
}

function fakeResourceShortageBuildHtml(int $readySeconds = 1329, string $readyLabel = '0:22:09'): string
{
    return <<<HTML
<div class="inlineIconList resourceWrapper charges">
    <div class="inlineIcon"><i class="r1Big"></i><span class="value">1.735</span></div>
    <div class="inlineIcon"><i class="r2Big"></i><span class="value">990</span></div>
    <div class="inlineIcon"><i class="r3Big"></i><span class="value">1.485</span></div>
    <div class="inlineIcon"><i class="r4Big"></i><span class="value">495</span></div>
    <div class="inlineIcon"><i class="cropConsumptionBig"></i><span class="value">2</span></div>
</div>
<div class="errorMessage">ستتوفر الموارد اللازمة يوم 30.05. عند الساعة 13:44<span class="hide"><span class="timer" counting="down" value="{$readySeconds}">{$readyLabel}</span></span></div>
HTML;
}

function fakeCropFieldRequiredBuildHtml(): string
{
    return <<<'HTML'
<div class="inlineIconList resourceWrapper charges">
    <div class="inlineIcon"><i class="r1Big"></i><span class="value">80</span></div>
    <div class="inlineIcon"><i class="r2Big"></i><span class="value">100</span></div>
    <div class="inlineIcon"><i class="r3Big"></i><span class="value">120</span></div>
    <div class="inlineIcon"><i class="r4Big"></i><span class="value">60</span></div>
    <div class="inlineIcon"><i class="cropConsumptionBig"></i><span class="value">1</span></div>
</div>
<div class="errorMessage">نقص الغذاء: يرجى تطوير حقل القمح أولاً</div>
HTML;
}

test('roman village can issue one field order and one building order in the same pass', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23378', 'قرية Marshal25', 1, [
                [
                    'building_name' => 'الحطاب',
                    'target_level' => 2,
                    'remaining_seconds' => 300,
                    'remaining_label' => '0:05:00',
                    'finish_label' => '12:30',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 1],
            ]),
            fakeDorf1Overview('23378', 'قرية Marshal25', 1, [
                [
                    'building_name' => 'المبنى الرئيسي',
                    'target_level' => 11,
                    'remaining_seconds' => 720,
                    'remaining_label' => '0:12:00',
                    'finish_label' => '12:37',
                ],
                [
                    'building_name' => 'الحطاب',
                    'target_level' => 2,
                    'remaining_seconds' => 295,
                    'remaining_label' => '0:04:55',
                    'finish_label' => '12:30',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 1],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
            ]),
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
            ]),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 1,
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 10,
    ]);

    $village->buildingTargets()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'target_level' => 11,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        /** @var array<string, array<string, mixed>> */
        public array $requestOptions = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;
            $this->requestOptions[$uri] = $options;

            return match ($uri) {
                '/dorf1.php?newdid=23378' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23378'),
                '/build.php?id=1' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=1&amp;gid=1&amp;action=build&amp;checksum=field001\'; return false;"></button>', 'https://example.com/build.php?id=1&gid=1'),
                '/dorf1.php?id=1&gid=1&action=build&checksum=field001' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=1&gid=1&action=build&checksum=field001'),
                '/build.php?id=26&gid=15' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf2.php?id=26&amp;gid=15&amp;action=build&amp;checksum=build015\'; return false;"></button>', 'https://example.com/build.php?id=26&gid=15'),
                '/dorf2.php?id=26&gid=15&action=build&checksum=build015' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php?id=26&gid=15&action=build&checksum=build015'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    $loadedVillage = $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']);

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $loadedVillage, $session);

    $finalVillage = $village->fresh();
    $fieldLog = ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->latest('id')->first();
    $buildingLog = ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->latest('id')->first();

    expect(ActivityLog::query()->where('activity_type', ActivityType::Build)->where('status', 'done')->count())->toBe(2);
    expect(ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->exists())->toBeTrue();
    expect(ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->exists())->toBeTrue();
    expect($session->requests)->toContain('/dorf1.php?newdid=23378');
    expect($session->requests)->toContain('/dorf1.php?id=1&gid=1&action=build&checksum=field001');
    expect($session->requests)->toContain('/dorf2.php?id=26&gid=15&action=build&checksum=build015');
    expect($session->requests)->toContain('/build.php?id=1');
    expect($session->requests)->not->toContain('/build.php?id=1&gid=1');
    expect($session->requestOptions['/build.php?id=1']['headers']['Referer'] ?? null)->toBe('https://example.com/dorf1.php');
    expect($session->requestOptions['/dorf1.php?id=1&gid=1&action=build&checksum=field001']['headers']['Referer'] ?? null)->toBe('https://example.com/build.php?id=1&gid=1');
    expect($session->requestOptions['/build.php?id=26&gid=15']['headers']['Referer'] ?? null)->toBe('https://example.com/dorf2.php');
    expect($fieldLog?->result['refresh_strategy'] ?? null)->toBe('action_response_dorf1');
    expect($fieldLog?->result['dorf2_effective_uri'] ?? null)->toBeNull();
    expect($buildingLog?->result['refresh_strategy'] ?? null)->toBe('action_response_dorf2_plus_dorf1');
    expect($buildingLog?->payload['target_level'] ?? null)->toBe(11);
    expect($buildingLog?->payload['final_target_level'] ?? null)->toBe(11);
    expect($finalVillage->runtimeState?->construction_entries)->toHaveCount(2);
    expect($finalVillage->runtimeState?->construction_entries[0]['building_name'] ?? null)->toBe('المبنى الرئيسي');
    expect($finalVillage->runtimeState?->construction_entries[0]['target_level'] ?? null)->toBe(11);
    expect($finalVillage->runtimeState?->construction_entries[1]['building_name'] ?? null)->toBe('الحطاب');
    expect($finalVillage->runtimeState?->construction_entries[1]['target_level'] ?? null)->toBe(2);
    expect($buildingLog?->payload['remaining_label'] ?? null)->toBe('0:12:00');
    expect($buildingLog?->result['overview_refreshed'] ?? null)->toBeTrue();
});

test('roman village uses hero resources for a field shortage even when its building order succeeds', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23379', 'Roman Hero Fields', 1, [
                [
                    'building_name' => 'الحطاب',
                    'target_level' => 2,
                    'remaining_seconds' => 300,
                    'remaining_label' => '0:05:00',
                    'finish_label' => '12:30',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 1],
            ]),
            fakeDorf1Overview('23379', 'Roman Hero Fields', 1, [
                [
                    'building_name' => 'المبنى الرئيسي',
                    'target_level' => 11,
                    'remaining_seconds' => 720,
                    'remaining_label' => '0:12:00',
                    'finish_label' => '12:37',
                ],
                [
                    'building_name' => 'الحطاب',
                    'target_level' => 2,
                    'remaining_seconds' => 295,
                    'remaining_label' => '0:04:55',
                    'finish_label' => '12:30',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 1],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
            ]),
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
            ]),
        ],
    );

    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create([
        'travian_village_id' => '23379',
        'name' => 'Roman Hero Fields',
        'is_active' => true,
    ]);
    $village->settings()->create([
        'inherit_from_account' => false,
        'field_priority' => ['wood' => 1, 'clay' => 1, 'iron' => 2, 'crop' => 2],
        'pause_fields' => false,
        'pause_buildings' => false,
        'hero_resources_enabled' => true,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);
    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 1,
    ]);
    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 10,
    ]);
    $village->buildingTargets()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'target_level' => 11,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    mock(UseHeroResourcesForConstruction::class)
        ->shouldReceive('handle')
        ->once()
        ->andReturnTrue();

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23379' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23379'),
                '/build.php?id=1' => $this->response(fakeResourceShortageBuildHtml(), 'https://example.com/build.php?id=1&gid=1'),
                '/build.php?id=1&reload=auto' => $this->response('<button onclick="window.location.href = \'/dorf1.php?id=1&amp;gid=1&amp;action=build&amp;checksum=hero-field\';"></button>', 'https://example.com/build.php?id=1&gid=1&reload=auto'),
                '/dorf1.php?id=1&gid=1&action=build&checksum=hero-field' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=1&gid=1&action=build&checksum=hero-field'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                '/build.php?id=26&gid=15' => $this->response('<button onclick="window.location.href = \'/dorf2.php?id=26&amp;gid=15&amp;action=build&amp;checksum=building\';"></button>', 'https://example.com/build.php?id=26&gid=15'),
                '/dorf2.php?id=26&gid=15&action=build&checksum=building' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php?id=26&gid=15&action=build&checksum=building'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                default => $this->response('<body></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle(
        $account->fresh(),
        $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']),
        $session,
    );

    expect($session->requests)->toContain('/build.php?id=1&reload=auto')
        ->and(ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->exists())->toBeTrue()
        ->and(ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->exists())->toBeTrue();
});

test('non roman village falls back to the explicit building target when no field can be upgraded', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23379', 'قرية Support', 2, [
                [
                    'building_name' => 'المبنى الرئيسي',
                    'target_level' => 6,
                    'remaining_seconds' => 600,
                    'remaining_label' => '0:10:00',
                    'finish_label' => '12:40',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 1],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 5],
            ]),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23379',
        'name' => 'قرية Support',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 1,
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 5,
    ]);

    $village->buildingTargets()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'target_level' => 8,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23379' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23379'),
                '/build.php?id=26&gid=15' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf2.php?id=26&amp;gid=15&amp;action=build&amp;checksum=build206\'; return false;"></button>', 'https://example.com/build.php?id=26&gid=15'),
                '/dorf2.php?id=26&gid=15&action=build&checksum=build206' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php?id=26&gid=15&action=build&checksum=build206'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    $loadedVillage = $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']);

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $loadedVillage, $session);

    expect(ActivityLog::query()->where('activity_type', ActivityType::Build)->where('status', 'done')->count())->toBe(1);
    expect(ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->exists())->toBeTrue();
    $buildingLog = ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->latest('id')->first();

    expect($buildingLog?->payload['target_level'] ?? null)->toBe(6);
    expect($buildingLog?->payload['final_target_level'] ?? null)->toBe(8);
    expect($session->requests)->toContain('/build.php?id=1');
    expect($session->requests)->toContain('/build.php?id=26&gid=15');
    expect($village->fresh()->runtimeState?->construction_entries)->toHaveCount(1);
    expect($village->fresh()->runtimeState?->construction_entries[0]['building_name'] ?? null)->toBe('المبنى الرئيسي');
    expect($village->fresh()->runtimeState?->construction_entries[0]['target_level'] ?? null)->toBe(6);
    expect($village->fresh()->runtimeState?->construction_entries[0]['remaining_label'] ?? null)->toBe('0:10:00');
});

test('roman village can still issue a field order while the building queue is already occupied', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23380', 'قرية Roman', 1, [
                [
                    'building_name' => 'المبنى الرئيسي',
                    'target_level' => 11,
                    'remaining_seconds' => 180,
                    'remaining_label' => '0:03:00',
                    'finish_label' => '12:45',
                ],
                [
                    'building_name' => 'الحطاب',
                    'target_level' => 2,
                    'remaining_seconds' => 300,
                    'remaining_label' => '0:05:00',
                    'finish_label' => '12:47',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 1],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
            ]),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23380',
        'name' => 'قرية Roman',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [
            [
                'building_name' => 'المبنى الرئيسي',
                'target_level' => 11,
                'remaining_seconds' => 180,
                'remaining_label' => '0:03:00',
                'finish_label' => '12:45',
            ],
        ],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 1,
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 10,
    ]);

    $village->buildingTargets()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'target_level' => 11,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23380' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23380'),
                '/build.php?id=1' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=1&amp;gid=1&amp;action=build&amp;checksum=field101\'; return false;"></button>', 'https://example.com/build.php?id=1&gid=1'),
                '/dorf1.php?id=1&gid=1&action=build&checksum=field101' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=1&gid=1&action=build&checksum=field101'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    $loadedVillage = $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']);

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $loadedVillage, $session);

    expect(ActivityLog::query()->where('activity_type', ActivityType::Build)->where('status', 'done')->count())->toBe(1);
    expect(ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->exists())->toBeTrue();
    expect($session->requests)->toContain('/build.php?id=1');
    expect($session->requests)->not->toContain('/build.php?id=26&gid=15');
    expect($village->fresh()->runtimeState?->construction_entries)->toHaveCount(2);
});

test('field automation can fall back to a lower priority field when the first choice is currently unavailable', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23381', 'قرية Flexible', 2, [
                [
                    'building_name' => 'حفرة الطين',
                    'target_level' => 4,
                    'remaining_seconds' => 120,
                    'remaining_label' => '0:02:00',
                    'finish_label' => '12:25',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 3],
                ['slot_id' => 2, 'building_gid' => 2, 'building_name' => 'حفرة الطين', 'current_level' => 3],
            ]),
        ],
        [
            fakeDorf2Overview(),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23381',
        'name' => 'قرية Flexible',
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
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 3,
    ]);

    $village->buildings()->create([
        'slot_id' => 2,
        'building_gid' => 2,
        'building_type' => 'حفرة الطين',
        'current_level' => 3,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23381' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23381'),
                '/build.php?id=1' => $this->response(fakeResourceShortageBuildHtml(), 'https://example.com/build.php?id=1&gid=1'),
                '/build.php?id=2' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=2&amp;gid=2&amp;action=build&amp;checksum=clay204\'; return false;"></button>', 'https://example.com/build.php?id=2&gid=2'),
                '/dorf1.php?id=2&gid=2&action=build&checksum=clay204' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=2&gid=2&action=build&checksum=clay204'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    $loadedVillage = $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']);

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $loadedVillage, $session);

    $buildLog = ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->latest('id')->first();

    expect($session->requests)->toContain('/build.php?id=1');
    expect($session->requests)->toContain('/build.php?id=2');
    expect($session->requests)->toContain('/dorf1.php?id=2&gid=2&action=build&checksum=clay204');
    expect($buildLog?->payload['field_key'] ?? null)->toBe('clay');
    expect($buildLog?->payload['building_name'] ?? null)->toBe('حفرة الطين');
    expect($buildLog?->payload['remaining_label'] ?? null)->toBe('0:02:00');
    expect($village->fresh()->runtimeState?->construction_entries[0]['building_name'] ?? null)->toBe('حفرة الطين');
    expect($village->fresh()->runtimeState?->construction_entries[0]['target_level'] ?? null)->toBe(4);
});

test('field automation does not execute candidates outside the visible dashboard schedule window', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23388',
        'name' => 'قرية Visible Window',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'inherit_from_account' => false,
        'field_priority' => [
            'clay' => 1,
            'iron' => 2,
            'crop' => 3,
            'wood' => 4,
        ],
        'pause_fields' => false,
        'pause_buildings' => true,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 1, 'building_gid' => 1, 'building_type' => 'الحطاب', 'current_level' => 6],
        ['slot_id' => 5, 'building_gid' => 2, 'building_type' => 'حفرة الطين', 'current_level' => 4],
        ['slot_id' => 6, 'building_gid' => 2, 'building_type' => 'حفرة الطين', 'current_level' => 5],
        ['slot_id' => 16, 'building_gid' => 2, 'building_type' => 'حفرة الطين', 'current_level' => 5],
        ['slot_id' => 18, 'building_gid' => 2, 'building_type' => 'حفرة الطين', 'current_level' => 5],
        ['slot_id' => 3, 'building_gid' => 3, 'building_type' => 'منجم حديد', 'current_level' => 4],
        ['slot_id' => 7, 'building_gid' => 3, 'building_type' => 'منجم حديد', 'current_level' => 4],
        ['slot_id' => 10, 'building_gid' => 3, 'building_type' => 'منجم حديد', 'current_level' => 4],
        ['slot_id' => 2, 'building_gid' => 4, 'building_type' => 'حقل القمح', 'current_level' => 4],
        ['slot_id' => 8, 'building_gid' => 4, 'building_type' => 'حقل القمح', 'current_level' => 4],
        ['slot_id' => 9, 'building_gid' => 4, 'building_type' => 'حقل القمح', 'current_level' => 4],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23388' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23388'),
                '/build.php?id=1' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=1&amp;gid=1&amp;action=build&amp;checksum=wood607\'; return false;"></button>', 'https://example.com/build.php?id=1&gid=1'),
                '/build.php?id=9' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=9&amp;gid=4&amp;action=build&amp;checksum=crop610\'; return false;"></button>', 'https://example.com/build.php?id=9&gid=4'),
                '/dorf1.php?id=9&gid=4&action=build&checksum=crop610' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=9&gid=4&action=build&checksum=crop610'),
                default => str_starts_with($uri, '/build.php?id=')
                    ? $this->response(fakeResourceShortageBuildHtml(), 'https://example.com'.$uri)
                    : $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    expect($session->requests)->not->toContain('/build.php?id=1');
    expect($session->requests)->toContain('/build.php?id=9');
    expect($session->requests)->toContain('/dorf1.php?id=9&gid=4&action=build&checksum=crop610');
    expect(ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->exists())->toBeTrue();
});

test('field automation limits adjacent priority families to a one level gap', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23387', 'قرية Priority Lead', 2, [
                [
                    'building_name' => 'منجم حديد',
                    'target_level' => 6,
                    'remaining_seconds' => 360,
                    'remaining_label' => '0:06:00',
                    'finish_label' => '12:30',
                ],
            ], [
                ['slot_id' => 4, 'building_gid' => 3, 'building_name' => 'منجم حديد', 'current_level' => 5],
                ['slot_id' => 5, 'building_gid' => 2, 'building_name' => 'حفرة الطين', 'current_level' => 6],
                ['slot_id' => 8, 'building_gid' => 4, 'building_name' => 'حقل القمح', 'current_level' => 5],
            ]),
        ],
        [
            fakeDorf2Overview(),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23387',
        'name' => 'قرية Priority Lead',
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
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 4, 'building_gid' => 3, 'building_type' => 'منجم حديد', 'current_level' => 5],
        ['slot_id' => 5, 'building_gid' => 2, 'building_type' => 'حفرة الطين', 'current_level' => 6],
        ['slot_id' => 8, 'building_gid' => 4, 'building_type' => 'حقل القمح', 'current_level' => 5],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23387' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23387'),
                '/build.php?id=4' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=4&amp;gid=3&amp;action=build&amp;checksum=iron506\'; return false;"></button>', 'https://example.com/build.php?id=4&gid=3'),
                '/dorf1.php?id=4&gid=3&action=build&checksum=iron506' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=4&gid=3&action=build&checksum=iron506'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    $buildLog = ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->latest('id')->first();

    expect($session->requests)->not->toContain('/build.php?id=5');
    expect($session->requests)->toContain('/build.php?id=4');
    expect($session->requests)->not->toContain('/build.php?id=8');
    expect($buildLog?->payload['field_key'] ?? null)->toBe('iron');
    expect($buildLog?->payload['building_name'] ?? null)->toBe('منجم حديد');
});

test('held schedule field is not skipped when resources are missing', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23381',
        'name' => 'قرية Held',
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
        'construction_schedule' => [
            'pinned' => [],
            'held' => ['field:1:4'],
        ],
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 3,
    ]);
    $village->buildings()->create([
        'slot_id' => 2,
        'building_gid' => 2,
        'building_type' => 'حفرة الطين',
        'current_level' => 3,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23381' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23381'),
                '/build.php?id=1' => $this->response(fakeResourceShortageBuildHtml(), 'https://example.com/build.php?id=1&gid=1'),
                '/build.php?id=2' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=2&amp;gid=2&amp;action=build&amp;checksum=clay204\'; return false;"></button>', 'https://example.com/build.php?id=2&gid=2'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    app(ExecuteVillageConstruction::class)->handle(
        $account->fresh(),
        $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']),
        $session,
    );

    $shortages = $village->fresh('runtimeState')->runtimeState?->construction_resource_shortages;

    expect($session->requests)->toContain('/build.php?id=1');
    expect($session->requests)->not->toContain('/build.php?id=2');
    expect(ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->exists())->toBeFalse();
    expect($shortages)->toHaveCount(1);
    expect($shortages[0]['schedule_key'] ?? null)->toBe('field:1:4');
});

test('pinned building order takes precedence over fields for roman dual queues', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23382',
        'name' => 'قرية Roman TOP',
        'is_active' => true,
    ]);

    $settings = $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'construction_schedule' => [
            'pinned' => ['building:26:6'],
            'held' => [],
        ],
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
        'target_level' => 7,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $method = new ReflectionMethod(ExecuteVillageConstruction::class, 'resolveRomanQueueOrder');
    $method->setAccessible(true);

    $order = $method->invoke(app(ExecuteVillageConstruction::class), [
        [
            'slot' => $fieldSlot,
            'field_key' => 'wood',
        ],
    ], [
        [
            'target' => $target,
            'current_slot' => $buildingSlot,
            'target_gid' => 15,
            'mode' => 'upgrade',
        ],
    ], $settings);

    expect($order)->toBe(['building', 'field']);
});

test('target pinned building order persists across changing building levels', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23386',
        'name' => 'قرية Main Repair TOP',
        'is_active' => true,
    ]);

    $settings = $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'construction_schedule' => [
            'pinned' => ['building-target:26:15'],
            'held' => [],
        ],
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
        'current_level' => 7,
    ]);
    $target = $village->buildingTargets()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'target_level' => 10,
        'priority' => 4,
        'is_enabled' => true,
    ]);

    $method = new ReflectionMethod(ExecuteVillageConstruction::class, 'resolveRomanQueueOrder');
    $method->setAccessible(true);

    $order = $method->invoke(app(ExecuteVillageConstruction::class), [
        [
            'slot' => $fieldSlot,
            'field_key' => 'wood',
        ],
    ], [
        [
            'target' => $target,
            'current_slot' => $buildingSlot,
            'target_gid' => 15,
            'mode' => 'upgrade',
        ],
    ], $settings);

    expect($order)->toBe(['building', 'field']);
});

test('pinned building order takes precedence over fields for single queue tribes', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23383',
        'name' => 'قرية Teuton TOP',
        'is_active' => true,
    ]);

    $settings = $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'construction_schedule' => [
            'pinned' => ['building:26:6'],
            'held' => [],
        ],
    ]);

    $fieldSlot = $village->buildings()->create([
        'slot_id' => 8,
        'building_gid' => 4,
        'building_type' => 'حقل القمح',
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
        'target_level' => 7,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $method = new ReflectionMethod(ExecuteVillageConstruction::class, 'resolveSingleQueueOrder');
    $method->setAccessible(true);

    $order = $method->invoke(app(ExecuteVillageConstruction::class), [
        [
            'slot' => $fieldSlot,
            'field_key' => 'crop',
        ],
    ], [
        [
            'target' => $target,
            'current_slot' => $buildingSlot,
            'target_gid' => 15,
            'mode' => 'upgrade',
        ],
    ], $settings);

    expect($order)->toBe(['building', 'field']);
});

test('pinned construction candidates are priority only and not schedule holds', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23384',
        'name' => 'قرية Strict TOP',
        'is_active' => true,
    ]);

    $settings = $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'construction_schedule' => [
            'pinned' => ['field:8:5'],
            'held' => [],
        ],
    ]);

    $fieldSlot = $village->buildings()->create([
        'slot_id' => 8,
        'building_gid' => 4,
        'building_type' => 'حقل القمح',
        'current_level' => 4,
    ]);

    $method = new ReflectionMethod(ExecuteVillageConstruction::class, 'candidateIsHeld');
    $method->setAccessible(true);

    $isHeld = $method->invoke(app(ExecuteVillageConstruction::class), [
        'slot' => $fieldSlot,
        'field_key' => 'crop',
    ], $settings, 'field');

    expect($isHeld)->toBeFalse();
});

test('building automation clamps stored resource bonus targets to max level five', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'is_active' => true,
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
        'current_level' => 5,
        'automation_enabled' => true,
    ]);
    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 14,
        'automation_enabled' => true,
    ]);
    $village->buildings()->create([
        'slot_id' => 30,
        'building_gid' => 10,
        'building_type' => 'المخزن',
        'current_level' => 20,
        'automation_enabled' => true,
    ]);
    $village->buildings()->create([
        'slot_id' => 31,
        'building_gid' => 11,
        'building_type' => 'مخزن الحبوب',
        'current_level' => 20,
        'automation_enabled' => true,
    ]);
    $target = $village->buildingTargets()->create([
        'slot_id' => 21,
        'building_gid' => 8,
        'building_type' => 'المطاحن',
        'target_level' => 12,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $method = new ReflectionMethod(ExecuteVillageConstruction::class, 'selectBuildingCandidates');
    $method->setAccessible(true);

    $candidates = $method->invoke(app(ExecuteVillageConstruction::class), $account, $village->fresh(['runtimeState', 'buildings', 'buildingTargets']));

    expect($candidates)->toBe([])
        ->and($target->fresh())->toBeNull();
});

test('building automation creates observed default building targets automatically', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'AMH7',
        'is_active' => true,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);
    $village->buildings()->create([
        'slot_id' => 28,
        'building_gid' => 8,
        'building_type' => 'المطاحن',
        'current_level' => 1,
        'automation_enabled' => true,
    ]);

    $method = new ReflectionMethod(ExecuteVillageConstruction::class, 'selectBuildingCandidates');
    $method->setAccessible(true);

    $candidates = $method->invoke(app(ExecuteVillageConstruction::class), $account, $village->fresh(['runtimeState', 'buildings', 'buildingTargets']));
    $village->refresh();
    $target = $village->buildingTargets()->where('slot_id', 28)->first();

    expect($target?->only(['building_gid', 'target_level', 'priority', 'is_enabled']))->toBe([
        'building_gid' => 8,
        'target_level' => 5,
        'priority' => 1,
        'is_enabled' => true,
    ])
        ->and($village->buildingTargets()->where('building_gid', 10)->exists())->toBeTrue()
        ->and($village->buildingTargets()->where('building_gid', 11)->exists())->toBeTrue()
        ->and(collect($candidates)->pluck('target_gid')->all())->toContain(8);
});

test('building automation balances equal priority warehouse and granary targets by current level', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'CR7',
        'is_active' => true,
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
        'current_level' => 6,
        'automation_enabled' => true,
    ]);
    $village->buildings()->create([
        'slot_id' => 24,
        'building_gid' => 11,
        'building_type' => 'مخزن الحبوب',
        'current_level' => 3,
        'automation_enabled' => true,
    ]);
    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 14,
        'automation_enabled' => true,
    ]);
    $village->buildingTargets()->create([
        'slot_id' => 19,
        'building_gid' => 10,
        'building_type' => 'المخزن',
        'target_level' => 12,
        'priority' => 1,
        'is_enabled' => true,
    ]);
    $village->buildingTargets()->create([
        'slot_id' => 24,
        'building_gid' => 11,
        'building_type' => 'مخزن الحبوب',
        'target_level' => 12,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $method = new ReflectionMethod(ExecuteVillageConstruction::class, 'selectBuildingCandidates');
    $method->setAccessible(true);

    $candidates = $method->invoke(app(ExecuteVillageConstruction::class), $account, $village->fresh(['runtimeState', 'buildings', 'buildingTargets']));

    expect(collect($candidates)->take(2)->pluck('target_gid')->all())->toBe([11, 10]);
});

test('building automation creates a level fourteen main building repair target when fixed slot is destroyed', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23379',
        'name' => 'Damaged',
        'is_active' => true,
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
        'building_gid' => 0,
        'building_type' => null,
        'current_level' => 0,
        'automation_enabled' => true,
    ]);

    $method = new ReflectionMethod(ExecuteVillageConstruction::class, 'selectBuildingCandidates');
    $method->setAccessible(true);

    $candidates = $method->invoke(app(ExecuteVillageConstruction::class), $account, $village->fresh(['runtimeState', 'buildings', 'buildingTargets']));
    $target = $village->fresh()->buildingTargets()->where('slot_id', 26)->first();

    expect($target?->only(['building_gid', 'target_level', 'priority', 'is_enabled']))->toBe([
        'building_gid' => 15,
        'target_level' => 14,
        'priority' => 1,
        'is_enabled' => true,
    ])
        ->and($village->fresh()->buildingTargets()->where('building_gid', 10)->exists())->toBeTrue()
        ->and($village->fresh()->buildingTargets()->where('building_gid', 11)->exists())->toBeTrue()
        ->and(collect($candidates)->pluck('target_gid')->all())->toContain(15)
        ->and(collect($candidates)->firstWhere('target_gid', 15)['mode'])->toBe('construct');
});

test('single queue village does not fall through to fields when held building is blocked', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23389', 'قرية Strict Building TOP', 2, [], [
                ['slot_id' => 8, 'building_gid' => 4, 'building_name' => 'حقل القمح', 'current_level' => 4],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 33, 'building_gid' => 22, 'building_name' => 'الأكاديمية', 'current_level' => 4],
            ]),
        ],
    );

    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create([
        'travian_village_id' => '23389',
        'name' => 'قرية Strict Building TOP',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
        'construction_schedule' => [
            'pinned' => ['building:33:5'],
            'held' => ['building:33:5'],
        ],
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 8,
        'building_gid' => 4,
        'building_type' => 'حقل القمح',
        'current_level' => 4,
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

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23389' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                '/build.php?id=33&gid=22' => $this->response('<div class="errorMessage">لا يمكن تطوير هذا المبنى الآن</div>', 'https://example.com/build.php?id=33&gid=22'),
                default => throw new RuntimeException('Unexpected request: '.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    expect($session->requests)->toContain('/build.php?id=33&gid=22');
    expect($session->requests)->not->toContain('/build.php?id=8');
    expect(ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->exists())->toBeFalse();
});

test('single queue village falls through when top building is blocked but not held', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23390', 'قرية Priority TOP', 2, [
                [
                    'building_name' => 'حقل القمح',
                    'target_level' => 5,
                    'remaining_seconds' => 120,
                    'remaining_label' => '0:02:00',
                    'finish_label' => '13:02',
                ],
            ], [
                ['slot_id' => 8, 'building_gid' => 4, 'building_name' => 'حقل القمح', 'current_level' => 4],
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 14],
                ['slot_id' => 30, 'building_gid' => 10, 'building_name' => 'المخزن', 'current_level' => 20],
                ['slot_id' => 31, 'building_gid' => 11, 'building_name' => 'مخزن الحبوب', 'current_level' => 20],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 14],
                ['slot_id' => 30, 'building_gid' => 10, 'building_name' => 'المخزن', 'current_level' => 20],
                ['slot_id' => 31, 'building_gid' => 11, 'building_name' => 'مخزن الحبوب', 'current_level' => 20],
                ['slot_id' => 33, 'building_gid' => 22, 'building_name' => 'الأكاديمية', 'current_level' => 4],
            ]),
        ],
    );

    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create([
        'travian_village_id' => '23390',
        'name' => 'قرية Priority TOP',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
        'construction_schedule' => [
            'pinned' => ['building:33:5', 'field:8:5'],
            'held' => [],
        ],
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 8,
        'building_gid' => 4,
        'building_type' => 'حقل القمح',
        'current_level' => 4,
    ]);
    $village->buildings()->create([
        'slot_id' => 33,
        'building_gid' => 22,
        'building_type' => 'الأكاديمية',
        'current_level' => 4,
    ]);
    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 14,
    ]);
    $village->buildings()->create([
        'slot_id' => 30,
        'building_gid' => 10,
        'building_type' => 'المخزن',
        'current_level' => 20,
    ]);
    $village->buildings()->create([
        'slot_id' => 31,
        'building_gid' => 11,
        'building_type' => 'مخزن الحبوب',
        'current_level' => 20,
    ]);
    $village->buildingTargets()->create([
        'slot_id' => 33,
        'building_gid' => 22,
        'building_type' => 'الأكاديمية',
        'target_level' => 5,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23390' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                '/build.php?id=33&gid=22' => $this->response('<div class="errorMessage">لا يمكن تطوير هذا المبنى الآن</div>', 'https://example.com/build.php?id=33&gid=22'),
                '/build.php?id=8' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=8&amp;gid=4&amp;action=build&amp;checksum=crop500\'; return false;"></button>', 'https://example.com/build.php?id=8&gid=4'),
                '/dorf1.php?id=8&gid=4&action=build&checksum=crop500' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=8&gid=4&action=build&checksum=crop500'),
                default => throw new RuntimeException('Unexpected request: '.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    expect($session->requests)->toContain('/build.php?id=33&gid=22');
    expect($session->requests)->toContain('/build.php?id=8');
    expect(ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->exists())->toBeTrue();
});

test('field automation upgrades crop when Travian requires crop before other fields', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23384', 'قرية Crop Recovery', 2, [
                [
                    'building_name' => 'حقل القمح',
                    'target_level' => 3,
                    'remaining_seconds' => 90,
                    'remaining_label' => '0:01:30',
                    'finish_label' => '12:35',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 2],
                ['slot_id' => 2, 'building_gid' => 2, 'building_name' => 'حفرة الطين', 'current_level' => 1],
                ['slot_id' => 4, 'building_gid' => 3, 'building_name' => 'منجم الحديد', 'current_level' => 1],
                ['slot_id' => 7, 'building_gid' => 3, 'building_name' => 'منجم الحديد', 'current_level' => 1],
                ['slot_id' => 8, 'building_gid' => 4, 'building_name' => 'حقل القمح', 'current_level' => 2],
            ]),
        ],
        [
            fakeDorf2Overview(),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23384',
        'name' => 'قرية Crop Recovery',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'inherit_from_account' => false,
        'field_priority' => [
            'wood' => 2,
            'clay' => 3,
            'iron' => 1,
            'crop' => 4,
        ],
        'pause_fields' => false,
        'pause_buildings' => true,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 1, 'building_gid' => 1, 'building_type' => 'الحطاب', 'current_level' => 2],
        ['slot_id' => 2, 'building_gid' => 2, 'building_type' => 'حفرة الطين', 'current_level' => 1],
        ['slot_id' => 4, 'building_gid' => 3, 'building_type' => 'منجم الحديد', 'current_level' => 1],
        ['slot_id' => 7, 'building_gid' => 3, 'building_type' => 'منجم الحديد', 'current_level' => 1],
        ['slot_id' => 8, 'building_gid' => 4, 'building_type' => 'حقل القمح', 'current_level' => 2],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23384' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23384'),
                '/build.php?id=4', '/build.php?id=7', '/build.php?id=2' => $this->response(fakeCropFieldRequiredBuildHtml(), 'https://example.com'.$uri),
                '/build.php?id=8' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=8&amp;gid=4&amp;action=build&amp;checksum=crop308\'; return false;"></button>', 'https://example.com/build.php?id=8&gid=4'),
                '/dorf1.php?id=8&gid=4&action=build&checksum=crop308' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=8&gid=4&action=build&checksum=crop308'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    $buildLog = ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->latest('id')->first();

    expect($session->requests)->toContain('/build.php?id=4');
    expect($session->requests)->toContain('/build.php?id=8');
    expect($session->requests)->toContain('/dorf1.php?id=8&gid=4&action=build&checksum=crop308');
    expect($buildLog?->payload['field_key'] ?? null)->toBe('crop');
    expect($buildLog?->payload['building_name'] ?? null)->toBe('حقل القمح');
    expect(ActivityLog::query()->where('message', 'Construction candidate blocked by build page.')->count())->toBe(0);
});

test('field automation prioritizes crop fields while crop production is negative', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23385', 'قرية Negative Crop', 2, [
                [
                    'building_name' => 'حقل القمح',
                    'target_level' => 3,
                    'remaining_seconds' => 75,
                    'remaining_label' => '0:01:15',
                    'finish_label' => '12:45',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 1],
                ['slot_id' => 8, 'building_gid' => 4, 'building_name' => 'حقل القمح', 'current_level' => 2],
            ]),
        ],
        [
            fakeDorf2Overview(),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23385',
        'name' => 'قرية Negative Crop',
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
        'prioritize_crop_fields_when_negative' => true,
        'pause_fields' => false,
        'pause_buildings' => true,
    ]);

    $village->resourceState()->create([
        'wood' => 1500,
        'clay' => 1500,
        'iron' => 1500,
        'crop' => 1500,
        'wood_production' => 100,
        'clay_production' => 100,
        'iron_production' => 100,
        'crop_production' => -5,
        'warehouse_capacity' => 4000,
        'granary_capacity' => 4000,
        'simulated_at' => now(),
        'server_reported_at' => now(),
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 1, 'building_gid' => 1, 'building_type' => 'الحطاب', 'current_level' => 1],
        ['slot_id' => 8, 'building_gid' => 4, 'building_type' => 'حقل القمح', 'current_level' => 2],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23385' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23385'),
                '/build.php?id=8' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=8&amp;gid=4&amp;action=build&amp;checksum=crop375\'; return false;"></button>', 'https://example.com/build.php?id=8&gid=4'),
                '/dorf1.php?id=8&gid=4&action=build&checksum=crop375' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=8&gid=4&action=build&checksum=crop375'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'resourceState', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    $buildLog = ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->latest('id')->first();

    expect($session->requests[1] ?? null)->toBe('/build.php?id=8');
    expect($session->requests)->not->toContain('/build.php?id=1');
    expect($buildLog?->payload['field_key'] ?? null)->toBe('crop');
});

test('field automation skips recently blocked build-page candidates and reaches the available crop field', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23387', 'CR7', 2, [
                [
                    'building_name' => 'حقل القمح',
                    'target_level' => 6,
                    'remaining_seconds' => 120,
                    'remaining_label' => '0:02:00',
                    'finish_label' => '01:25',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 6],
                ['slot_id' => 2, 'building_gid' => 2, 'building_name' => 'حفرة الطين', 'current_level' => 6],
                ['slot_id' => 8, 'building_gid' => 4, 'building_name' => 'حقل القمح', 'current_level' => 5],
            ]),
        ],
        [
            fakeDorf2Overview(),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);
    $village = $account->villages()->create([
        'travian_village_id' => '23387',
        'name' => 'CR7',
        'is_active' => true,
    ]);
    $village->settings()->create([
        'inherit_from_account' => false,
        'field_priority' => [
            'wood' => 1,
            'clay' => 1,
            'iron' => 2,
            'crop' => 2,
        ],
        'pause_fields' => false,
        'pause_buildings' => true,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 1, 'building_gid' => 1, 'building_type' => 'الحطاب', 'current_level' => 6],
        ['slot_id' => 2, 'building_gid' => 2, 'building_type' => 'حفرة الطين', 'current_level' => 6],
        ['slot_id' => 8, 'building_gid' => 4, 'building_type' => 'حقل القمح', 'current_level' => 5],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    foreach ([
        ['schedule_key' => 'field:1:7', 'slot_id' => 1, 'field_key' => 'wood'],
        ['schedule_key' => 'field:2:7', 'slot_id' => 2, 'field_key' => 'clay'],
    ] as $payload) {
        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Build,
            'status' => ActivityLogStatus::Pending,
            'payload' => [
                'queue_kind' => 'field',
                'target_level' => 7,
                ...$payload,
            ],
            'message' => 'Construction candidate blocked by build page.',
            'executed_at' => now()->subMinute(),
        ]);
    }

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23387' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23387'),
                '/build.php?id=8' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=8&amp;gid=4&amp;action=build&amp;checksum=crop777\'; return false;"></button>', 'https://example.com/build.php?id=8&gid=4'),
                '/dorf1.php?id=8&gid=4&action=build&checksum=crop777' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=8&gid=4&action=build&checksum=crop777'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    $buildLog = ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->latest('id')->first();

    expect($session->requests)->not->toContain('/build.php?id=1');
    expect($session->requests)->not->toContain('/build.php?id=2');
    expect($session->requests)->toContain('/build.php?id=8');
    expect($buildLog?->payload['field_key'] ?? null)->toBe('crop');
});

test('field automation records resource shortage when every field candidate is blocked', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23383',
        'name' => 'قرية Waiting',
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
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 3,
    ]);

    $village->buildings()->create([
        'slot_id' => 2,
        'building_gid' => 2,
        'building_type' => 'حفرة الطين',
        'current_level' => 3,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23383' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23383'),
                '/build.php?id=1' => $this->response(fakeResourceShortageBuildHtml(1329), 'https://example.com/build.php?id=1&gid=1'),
                '/build.php?id=2' => $this->response(fakeResourceShortageBuildHtml(900, '0:15:00'), 'https://example.com/build.php?id=2&gid=2'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    $shortages = $village->fresh('runtimeState')->runtimeState?->construction_resource_shortages;

    expect(ActivityLog::query()->where('activity_type', ActivityType::Build)->where('status', 'done')->exists())->toBeFalse();
    expect($session->requests)->toContain('/build.php?id=1');
    expect($session->requests)->toContain('/build.php?id=2');
    expect($shortages)->toHaveCount(2);
    expect($shortages[0]['slot_id'] ?? null)->toBe(2);
    expect($shortages[0]['resource_ready_seconds'] ?? null)->toBe(900);
    expect($shortages[0]['required_resources'] ?? [])->toMatchArray([
        'wood' => 1735,
        'clay' => 990,
        'iron' => 1485,
        'crop' => 495,
        'crop_consumption' => 2,
    ]);
    expect($shortages[0]['block_message'] ?? null)->toContain('ستتوفر الموارد اللازمة');
});

test('field priority balancing prevents a preferred resource from running too far ahead', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23382', 'قرية Balanced', 2, [
                [
                    'building_name' => 'حفرة الطين',
                    'target_level' => 5,
                    'remaining_seconds' => 160,
                    'remaining_label' => '0:02:40',
                    'finish_label' => '12:26',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 6],
                ['slot_id' => 2, 'building_gid' => 2, 'building_name' => 'حفرة الطين', 'current_level' => 4],
                ['slot_id' => 3, 'building_gid' => 3, 'building_name' => 'منجم الحديد', 'current_level' => 4],
                ['slot_id' => 4, 'building_gid' => 4, 'building_name' => 'حقل القمح', 'current_level' => 4],
            ]),
        ],
        [
            fakeDorf2Overview(),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23382',
        'name' => 'قرية Balanced',
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
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 1, 'building_gid' => 1, 'building_type' => 'الحطاب', 'current_level' => 6],
        ['slot_id' => 2, 'building_gid' => 2, 'building_type' => 'حفرة الطين', 'current_level' => 4],
        ['slot_id' => 3, 'building_gid' => 3, 'building_type' => 'منجم الحديد', 'current_level' => 4],
        ['slot_id' => 4, 'building_gid' => 4, 'building_type' => 'حقل القمح', 'current_level' => 4],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23382' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23382'),
                '/build.php?id=2' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=2&amp;gid=2&amp;action=build&amp;checksum=clay502\'; return false;"></button>', 'https://example.com/build.php?id=2&gid=2'),
                '/dorf1.php?id=2&gid=2&action=build&checksum=clay502' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=2&gid=2&action=build&checksum=clay502'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    $buildLog = ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->latest('id')->first();

    expect($session->requests)->not->toContain('/build.php?id=1');
    expect($session->requests)->toContain('/build.php?id=2');
    expect($buildLog?->payload['field_key'] ?? null)->toBe('clay');
    expect($buildLog?->payload['target_level'] ?? null)->toBe(5);
});

test('field priority balancing uses the lowest field in each resource family', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23386', 'قرية Family Balance', 2, [
                [
                    'building_name' => 'منجم الحديد',
                    'target_level' => 4,
                    'remaining_seconds' => 160,
                    'remaining_label' => '0:02:40',
                    'finish_label' => '12:26',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 5],
                ['slot_id' => 2, 'building_gid' => 2, 'building_name' => 'حفرة الطين', 'current_level' => 5],
                ['slot_id' => 3, 'building_gid' => 3, 'building_name' => 'منجم الحديد', 'current_level' => 5],
                ['slot_id' => 5, 'building_gid' => 3, 'building_name' => 'منجم الحديد', 'current_level' => 3],
                ['slot_id' => 4, 'building_gid' => 4, 'building_name' => 'حقل القمح', 'current_level' => 4],
            ]),
        ],
        [
            fakeDorf2Overview(),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23386',
        'name' => 'قرية Family Balance',
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
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 1, 'building_gid' => 1, 'building_type' => 'الحطاب', 'current_level' => 5],
        ['slot_id' => 2, 'building_gid' => 2, 'building_type' => 'حفرة الطين', 'current_level' => 5],
        ['slot_id' => 3, 'building_gid' => 3, 'building_type' => 'منجم الحديد', 'current_level' => 5],
        ['slot_id' => 5, 'building_gid' => 3, 'building_type' => 'منجم الحديد', 'current_level' => 3],
        ['slot_id' => 4, 'building_gid' => 4, 'building_type' => 'حقل القمح', 'current_level' => 4],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23386' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23386'),
                '/build.php?id=5' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=5&amp;gid=3&amp;action=build&amp;checksum=iron304\'; return false;"></button>', 'https://example.com/build.php?id=5&gid=3'),
                '/dorf1.php?id=5&gid=3&action=build&checksum=iron304' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=5&gid=3&action=build&checksum=iron304'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    $buildLog = ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->latest('id')->first();

    expect($session->requests)->not->toContain('/build.php?id=2');
    expect($session->requests)->toContain('/build.php?id=5');
    expect($buildLog?->payload['field_key'] ?? null)->toBe('iron');
    expect($buildLog?->payload['target_level'] ?? null)->toBe(4);
});

test('building automation constructs a new town hall through the browser-like dorf2 flow', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23384', 'قرية Build', 1, [
                [
                    'building_name' => 'البلدية',
                    'target_level' => 1,
                    'remaining_seconds' => 900,
                    'remaining_label' => '0:15:00',
                    'finish_label' => '13:00',
                ],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
                ['slot_id' => 31, 'building_gid' => 22, 'building_name' => 'الأكاديمية', 'current_level' => 10],
                ['slot_id' => 37, 'building_gid' => 24, 'building_name' => 'البلدية', 'current_level' => 0],
            ]),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);
    $village = $account->villages()->create([
        'travian_village_id' => '23384',
        'name' => 'قرية Build',
        'is_active' => true,
    ]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => true,
        'pause_buildings' => false,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 26, 'building_gid' => 15, 'building_type' => 'المبنى الرئيسي', 'current_level' => 10],
        ['slot_id' => 31, 'building_gid' => 22, 'building_type' => 'الأكاديمية', 'current_level' => 10],
        ['slot_id' => 37, 'building_gid' => 0, 'building_type' => null, 'current_level' => 0],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    $village->buildingTargets()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'target_level' => 1,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        /** @var array<string, array<string, mixed>> */
        public array $requestOptions = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;
            $this->requestOptions[$uri] = $options;

            return match ($uri) {
                '/dorf1.php?newdid=23384' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                '/build.php?id=37&category=1' => $this->response((string) file_get_contents(base_path('tests/Fixtures/travian-samples/test04/construct-building/step02-build-page-response.md')), 'https://example.com/build.php?id=37&category=1'),
                '/dorf2.php?id=37&gid=24&action=build&checksum=09fb23' => $this->response((string) file_get_contents(base_path('tests/Fixtures/travian-samples/test04/construct-building/step03-press-constructButton-response.md')), 'https://example.com/dorf2.php?id=37&gid=24&action=build&checksum=09fb23'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                default => $this->response('<body class="village2"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    $buildLog = ActivityLog::query()->where('message', 'Building construction order issued successfully.')->latest('id')->first();

    expect($session->requests)->toContain('/dorf1.php?newdid=23384');
    expect($session->requests)->toContain('/dorf2.php');
    expect($session->requests)->toContain('/build.php?id=37&category=1');
    expect($session->requests)->toContain('/dorf2.php?id=37&gid=24&action=build&checksum=09fb23');
    expect($session->requestOptions['/dorf2.php']['headers']['Referer'] ?? null)->toBe('https://example.com/dorf1.php');
    expect($session->requestOptions['/build.php?id=37&category=1']['headers']['Referer'] ?? null)->toBe('https://example.com/dorf2.php');
    expect($buildLog?->payload['building_gid'] ?? null)->toBe(24);
    expect($buildLog?->payload['mode'] ?? null)->toBe('construct');
});

test('building automation upgrades an existing building through its build page', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23385', 'قرية Upgrade', 1, [
                [
                    'building_name' => 'السوق',
                    'target_level' => 2,
                    'remaining_seconds' => 500,
                    'remaining_label' => '0:08:20',
                    'finish_label' => '13:05',
                ],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 32, 'building_gid' => 17, 'building_name' => 'السوق', 'current_level' => 1],
            ]),
        ],
    );

    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create([
        'travian_village_id' => '23385',
        'name' => 'قرية Upgrade',
        'is_active' => true,
    ]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => true,
        'pause_buildings' => false,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);
    $village->buildings()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'current_level' => 1,
    ]);
    $village->buildingTargets()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'target_level' => 3,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23385' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                '/build.php?id=32&gid=17' => $this->response((string) file_get_contents(base_path('tests/Fixtures/travian-samples/test04/upgrade-building-already-exist/step02-build-page-response.md')), 'https://example.com/build.php?id=32&gid=17'),
                '/dorf2.php?id=32&gid=17&action=build&checksum=5d1a0b' => $this->response((string) file_get_contents(base_path('tests/Fixtures/travian-samples/test04/upgrade-building-already-exist/step03-press-upgradeButton-response.md')), 'https://example.com/dorf2.php?id=32&gid=17&action=build&checksum=5d1a0b'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                default => $this->response('<body class="village2"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    $buildLog = ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->latest('id')->first();

    expect($session->requests)->toContain('/build.php?id=32&gid=17');
    expect($session->requests)->toContain('/dorf2.php?id=32&gid=17&action=build&checksum=5d1a0b');
    expect($buildLog?->payload['target_level'] ?? null)->toBe(2);
    expect($buildLog?->payload['final_target_level'] ?? null)->toBe(3);
});

test('building automation refreshes stale building level from live dorf2 before upgrade', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23385', 'قرية Upgrade', 1, [
                [
                    'building_name' => 'السوق',
                    'target_level' => 3,
                    'remaining_seconds' => 500,
                    'remaining_label' => '0:08:20',
                    'finish_label' => '13:05',
                ],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 32, 'building_gid' => 17, 'building_name' => 'السوق', 'current_level' => 2],
            ]),
        ],
    );

    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create([
        'travian_village_id' => '23385',
        'name' => 'قرية Stale Upgrade',
        'is_active' => true,
    ]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => true,
        'pause_buildings' => false,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);
    $village->buildings()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'current_level' => 1,
    ]);
    $village->buildingTargets()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'target_level' => 3,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23385' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                '/build.php?id=32&gid=17' => $this->response((string) file_get_contents(base_path('tests/Fixtures/travian-samples/test04/upgrade-building-already-exist/step02-build-page-response.md')), 'https://example.com/build.php?id=32&gid=17'),
                '/dorf2.php?id=32&gid=17&action=build&checksum=5d1a0b' => $this->response((string) file_get_contents(base_path('tests/Fixtures/travian-samples/test04/upgrade-building-already-exist/step03-press-upgradeButton-response.md')), 'https://example.com/dorf2.php?id=32&gid=17&action=build&checksum=5d1a0b'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                default => $this->response('<body class="village2"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    $buildLog = ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->latest('id')->first();

    expect($buildLog?->payload['current_level'] ?? null)->toBe(2);
    expect($buildLog?->payload['target_level'] ?? null)->toBe(3);
    expect($village->fresh()->buildings()->where('slot_id', 32)->first()?->current_level)->toBe(2);
});

test('building automation skips stale building target already completed in live dorf2', function () {
    bindConstructionRefreshSnapshots(
        [fakeDorf1Overview('23385', 'قرية Completed', 1)],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 14],
                ['slot_id' => 30, 'building_gid' => 10, 'building_name' => 'المخزن', 'current_level' => 20],
                ['slot_id' => 31, 'building_gid' => 11, 'building_name' => 'مخزن الحبوب', 'current_level' => 20],
                ['slot_id' => 32, 'building_gid' => 17, 'building_name' => 'السوق', 'current_level' => 3],
            ]),
        ],
    );

    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create([
        'travian_village_id' => '23385',
        'name' => 'قرية Completed',
        'is_active' => true,
    ]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => true,
        'pause_buildings' => false,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);
    $village->buildings()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'current_level' => 1,
    ]);
    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 14,
    ]);
    $village->buildings()->create([
        'slot_id' => 30,
        'building_gid' => 10,
        'building_type' => 'المخزن',
        'current_level' => 20,
    ]);
    $village->buildings()->create([
        'slot_id' => 31,
        'building_gid' => 11,
        'building_type' => 'مخزن الحبوب',
        'current_level' => 20,
    ]);
    $target = $village->buildingTargets()->create([
        'slot_id' => 32,
        'building_gid' => 17,
        'building_type' => 'السوق',
        'target_level' => 3,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23385' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                default => throw new RuntimeException('Unexpected request: '.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    expect($session->requests)->toBe(['/dorf1.php?newdid=23385', '/dorf2.php']);
    expect(ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->exists())->toBeFalse();
    expect($village->fresh()->buildings()->where('slot_id', 32)->first()?->current_level)->toBe(3);
    expect($target->fresh())->toBeNull();
});

test('building automation skips a blocked target and executes the next valid target', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23386', 'قرية Skip', 1, [
                [
                    'building_name' => 'البلدية',
                    'target_level' => 1,
                    'remaining_seconds' => 900,
                    'remaining_label' => '0:15:00',
                    'finish_label' => '13:00',
                ],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
                ['slot_id' => 31, 'building_gid' => 22, 'building_name' => 'الأكاديمية', 'current_level' => 10],
                ['slot_id' => 37, 'building_gid' => 24, 'building_name' => 'البلدية', 'current_level' => 0],
            ]),
        ],
    );

    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create([
        'travian_village_id' => '23386',
        'name' => 'قرية Skip',
        'is_active' => true,
    ]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => true,
        'pause_buildings' => false,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 26, 'building_gid' => 15, 'building_type' => 'المبنى الرئيسي', 'current_level' => 10],
        ['slot_id' => 31, 'building_gid' => 22, 'building_type' => 'الأكاديمية', 'current_level' => 10],
        ['slot_id' => 37, 'building_gid' => 0, 'building_type' => null, 'current_level' => 0],
        ['slot_id' => 38, 'building_gid' => 0, 'building_type' => null, 'current_level' => 0],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    $village->buildingTargets()->create([
        'slot_id' => 38,
        'building_gid' => 41,
        'building_type' => 'بئر سقي الخيول',
        'target_level' => 1,
        'priority' => 1,
        'is_enabled' => true,
    ]);
    $village->buildingTargets()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'target_level' => 1,
        'priority' => 2,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23386' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                '/build.php?id=37&category=1' => $this->response((string) file_get_contents(base_path('tests/Fixtures/travian-samples/test04/construct-building/step02-build-page-response.md')), 'https://example.com/build.php?id=37&category=1'),
                '/dorf2.php?id=37&gid=24&action=build&checksum=09fb23' => $this->response((string) file_get_contents(base_path('tests/Fixtures/travian-samples/test04/construct-building/step03-press-constructButton-response.md')), 'https://example.com/dorf2.php?id=37&gid=24&action=build&checksum=09fb23'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                default => $this->response('<body class="village2"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    expect(ActivityLog::query()->where('message', 'Building candidate blocked by construction rules.')->exists())->toBeTrue();
    expect(ActivityLog::query()->where('message', 'Building construction order issued successfully.')->latest('id')->first()?->payload['building_gid'] ?? null)->toBe(24);
});

test('building automation upgrades configured prerequisite before blocked academy', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23388', 'قرية Academy', 1, [
                [
                    'building_name' => 'الثكنة',
                    'target_level' => 3,
                    'remaining_seconds' => 600,
                    'remaining_label' => '0:10:00',
                    'finish_label' => '13:10',
                ],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
                ['slot_id' => 33, 'building_gid' => 22, 'building_name' => 'الأكاديمية', 'current_level' => 0],
                ['slot_id' => 34, 'building_gid' => 19, 'building_name' => 'الثكنة', 'current_level' => 2],
            ]),
        ],
    );

    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create([
        'travian_village_id' => '23388',
        'name' => 'قرية Academy',
        'is_active' => true,
    ]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => true,
        'pause_buildings' => false,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 26, 'building_gid' => 15, 'building_type' => 'المبنى الرئيسي', 'current_level' => 10],
        ['slot_id' => 33, 'building_gid' => 0, 'building_type' => null, 'current_level' => 0],
        ['slot_id' => 34, 'building_gid' => 19, 'building_type' => 'الثكنة', 'current_level' => 2],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    $village->buildingTargets()->create([
        'slot_id' => 33,
        'building_gid' => 22,
        'building_type' => 'الأكاديمية',
        'target_level' => 5,
        'priority' => 5,
        'is_enabled' => true,
    ]);
    $village->buildingTargets()->create([
        'slot_id' => 34,
        'building_gid' => 19,
        'building_type' => 'الثكنة',
        'target_level' => 3,
        'priority' => 5,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23388' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                '/build.php?id=34&gid=19' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf2.php?id=34&amp;gid=19&amp;action=build&amp;checksum=barracks003\'; return false;"></button>', 'https://example.com/build.php?id=34&gid=19'),
                '/dorf2.php?id=34&gid=19&action=build&checksum=barracks003' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php?id=34&gid=19&action=build&checksum=barracks003'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                default => $this->response('<body class="village2"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    expect($session->requests)->toContain('/build.php?id=34&gid=19');
    expect($session->requests)->toContain('/dorf2.php?id=34&gid=19&action=build&checksum=barracks003');
    expect(ActivityLog::query()->where('message', 'Building candidate blocked by construction rules.')->exists())->toBeFalse();
    expect(ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->latest('id')->first()?->payload['building_gid'] ?? null)->toBe(19);
});

test('building automation records resource shortage when every building target lacks resources', function () {
    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create([
        'travian_village_id' => '23387',
        'name' => 'قرية Shortage',
        'is_active' => true,
    ]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => true,
        'pause_buildings' => false,
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 26, 'building_gid' => 15, 'building_type' => 'المبنى الرئيسي', 'current_level' => 10],
        ['slot_id' => 31, 'building_gid' => 22, 'building_type' => 'الأكاديمية', 'current_level' => 10],
        ['slot_id' => 37, 'building_gid' => 0, 'building_type' => null, 'current_level' => 0],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    $village->buildingTargets()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'target_level' => 1,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        public function get(string $uri, array $options = []): SessionResponse
        {
            return match ($uri) {
                '/dorf1.php?newdid=23387' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                '/build.php?id=37&category=1' => $this->response(fakeResourceShortageBuildHtml(1329), 'https://example.com/build.php?id=37&category=1'),
                default => $this->response('<body class="village2"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    $shortages = $village->fresh('runtimeState')->runtimeState?->construction_resource_shortages;

    expect(ActivityLog::query()->where('message', 'Building construction order issued successfully.')->exists())->toBeFalse();
    expect($shortages)->toHaveCount(1);
    expect($shortages[0]['queue_kind'] ?? null)->toBe('building');
    expect($shortages[0]['building_gid'] ?? null)->toBe(24);
    expect($shortages[0]['resource_ready_seconds'] ?? null)->toBe(1329);
});

test('construction uses hero resources before marketplace support and immediately retries the build', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23389', 'قرية Hero Stock', 1, [
                [
                    'building_name' => 'البلدية',
                    'target_level' => 1,
                    'remaining_seconds' => 900,
                    'remaining_label' => '0:15:00',
                    'finish_label' => '13:00',
                ],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
                ['slot_id' => 31, 'building_gid' => 22, 'building_name' => 'الأكاديمية', 'current_level' => 10],
                ['slot_id' => 37, 'building_gid' => 0, 'building_name' => null, 'current_level' => 0, 'is_empty' => true],
            ]),
            fakeDorf2Overview([
                ['slot_id' => 37, 'building_gid' => 24, 'building_name' => 'البلدية', 'current_level' => 0],
            ]),
        ],
    );

    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create([
        'travian_village_id' => '23389',
        'name' => 'قرية Hero Stock',
        'is_active' => true,
    ]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => true,
        'pause_buildings' => false,
        'hero_resources_enabled' => true,
        'support_enabled' => true,
    ]);
    $village->resourceState()->create([
        'wood' => 1300,
        'clay' => 900,
        'iron' => 1000,
        'crop' => 400,
        'wood_production' => 0,
        'clay_production' => 0,
        'iron_production' => 0,
        'crop_production' => 0,
        'warehouse_capacity' => 9600,
        'granary_capacity' => 6300,
        'server_reported_at' => now(),
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 26, 'building_gid' => 15, 'building_type' => 'المبنى الرئيسي', 'current_level' => 10],
        ['slot_id' => 31, 'building_gid' => 22, 'building_type' => 'الأكاديمية', 'current_level' => 10],
        ['slot_id' => 37, 'building_gid' => 0, 'building_type' => null, 'current_level' => 0],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    $village->buildingTargets()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'target_level' => 1,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $getRequests = [];

        /** @var list<array{uri: string, payload: array<string, mixed>, options: array<string, mixed>}> */
        public array $jsonRequests = [];

        /** @var list<array{uri: string, payload: array<string, mixed>, options: array<string, mixed>}> */
        public array $putRequests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->getRequests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23389' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                '/build.php?id=37&category=1' => $this->response(fakeResourceShortageBuildHtml(1329), 'https://example.com/build.php?id=37&category=1'),
                '/build.php?id=37&category=1&reload=auto' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf2.php?id=37&amp;gid=24&amp;action=build&amp;checksum=hero\'; return false;"></button>', 'https://example.com/build.php?id=37&category=1&reload=auto'),
                '/dorf2.php?id=37&gid=24&action=build&checksum=hero' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php?id=37&gid=24&action=build&checksum=hero'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                default => $this->response('<body></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            $this->jsonRequests[] = [
                'uri' => $uri,
                'payload' => $payload,
                'options' => $options,
            ];

            if ($uri === '/api/v1/graphql') {
                return $this->response(json_encode([
                    'data' => [
                        'ownPlayer' => [
                            'hero' => [
                                'inventory' => [
                                    ['id' => 38293, 'amount' => 90000, 'placeId' => 1, 'name' => 'الخشب', 'typeId' => 145, 'slot' => 'inventory'],
                                    ['id' => 38294, 'amount' => 90000, 'placeId' => 2, 'name' => 'طين', 'typeId' => 146, 'slot' => 'inventory'],
                                    ['id' => 38295, 'amount' => 90000, 'placeId' => 3, 'name' => 'الحديد', 'typeId' => 147, 'slot' => 'inventory'],
                                    ['id' => 38296, 'amount' => 90000, 'placeId' => 4, 'name' => 'القمح', 'typeId' => 148, 'slot' => 'inventory'],
                                ],
                            ],
                            'village' => [
                                'id' => 23389,
                                'name' => 'قرية Hero Stock',
                                'resources' => [
                                    'lumberStock' => 1300,
                                    'clayStock' => 900,
                                    'ironStock' => 1000,
                                    'cropStock' => 400,
                                    'maxStorageCapacity' => 9600,
                                    'maxCropStorageCapacity' => 6300,
                                ],
                            ],
                        ],
                    ],
                ], JSON_THROW_ON_ERROR), 'https://example.com/api/v1/graphql');
            }

            return $this->response('{"success":true}', 'https://example.com'.$uri);
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            $this->putRequests[] = [
                'uri' => $uri,
                'payload' => $payload,
                'options' => $options,
            ];

            return new SessionResponse(200, '', 'https://example.com'.$uri, [
                'x-nonce' => ['nonce-'.$payload['itemId']],
            ]);
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'resourceState', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    expect($session->getRequests)->toContain('/build.php?id=37&category=1&reload=auto');
    expect(array_map(static fn (array $request): int => (int) $request['payload']['amount'], $session->putRequests))->toBe([435, 90, 485, 95]);
    $heroLog = ActivityLog::query()
        ->where('activity_type', ActivityType::Hero)
        ->where('message', 'Hero resources moved to village for construction.')
        ->first();

    expect($heroLog)->not->toBeNull()
        ->and($heroLog->payload['effective_uri'] ?? null)->toBe('https://example.com/api/v1/graphql')
        ->and($heroLog->payload['status_code'] ?? null)->toBe(200);
    expect(ActivityLog::query()->where('activity_type', ActivityType::Build)->where('message', 'Building construction order issued successfully.')->exists())->toBeTrue();
    expect(ActivityLog::query()->where('activity_type', ActivityType::Transfer)->exists())->toBeFalse();
});

test('construction skips hero resources and falls back to trade when hero resources are disabled for the village', function () {
    bindConstructionRefreshSnapshots(
        [fakeDorf1Overview('23390', 'قرية Trade Fallback', 1)],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
                ['slot_id' => 31, 'building_gid' => 22, 'building_name' => 'الأكاديمية', 'current_level' => 10],
                ['slot_id' => 37, 'building_gid' => 0, 'building_name' => null, 'current_level' => 0, 'is_empty' => true],
            ]),
        ],
    );

    $account = Account::factory()->create(['server_url' => 'https://example.com/']);
    $village = $account->villages()->create([
        'travian_village_id' => '23390',
        'name' => 'قرية Trade Fallback',
        'is_active' => true,
    ]);
    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => true,
        'pause_buildings' => false,
        'hero_resources_enabled' => false,
        'support_enabled' => true,
    ]);
    $village->resourceState()->create([
        'wood' => 1300,
        'clay' => 900,
        'iron' => 1000,
        'crop' => 400,
        'wood_production' => 0,
        'clay_production' => 0,
        'iron_production' => 0,
        'crop_production' => 0,
        'warehouse_capacity' => 9600,
        'granary_capacity' => 6300,
        'server_reported_at' => now(),
    ]);
    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    foreach ([
        ['slot_id' => 26, 'building_gid' => 15, 'building_type' => 'المبنى الرئيسي', 'current_level' => 10],
        ['slot_id' => 31, 'building_gid' => 22, 'building_type' => 'الأكاديمية', 'current_level' => 10],
        ['slot_id' => 37, 'building_gid' => 0, 'building_type' => null, 'current_level' => 0],
    ] as $slot) {
        $village->buildings()->create($slot);
    }

    $village->buildingTargets()->create([
        'slot_id' => 37,
        'building_gid' => 24,
        'building_type' => 'البلدية',
        'target_level' => 1,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        public function get(string $uri, array $options = []): SessionResponse
        {
            return match ($uri) {
                '/dorf1.php?newdid=23390' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                '/build.php?id=37&category=1' => $this->response(fakeResourceShortageBuildHtml(1329), 'https://example.com/build.php?id=37&category=1'),
                default => $this->response('<body></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('Hero resources should be skipped when disabled.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('Hero resources should be skipped when disabled.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(200, $body, $effectiveUri, []);
        }
    };

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $village->fresh(['settings', 'resourceState', 'runtimeState', 'buildings', 'buildingTargets']), $session);

    expect(ActivityLog::query()->where('activity_type', ActivityType::Hero)->exists())->toBeFalse();
    expect(ActivityLog::query()->where('activity_type', ActivityType::Transfer)->exists())->toBeFalse();
});

test('village construction rethrows transient connection failures for account backoff', function () {
    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'Timeout Village',
        'is_active' => true,
    ]);

    $session = new class implements AccountSession
    {
        public function get(string $uri, array $options = []): SessionResponse
        {
            throw new RuntimeException('cURL error 28: Operation timed out after 20001 milliseconds with 80821 out of 176843 bytes received');
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            return $this->get($uri, $options);
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            return $this->get($uri, $options);
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            return $this->get($uri, $options);
        }

        public function persist(): void {}
    };

    expect(fn () => app(ExecuteVillageConstruction::class)->handle($account, $village, $session))
        ->toThrow(RuntimeException::class, 'cURL error 28');
});
