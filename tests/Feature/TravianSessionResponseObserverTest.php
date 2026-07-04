<?php

use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Session\TravianSessionResponseObserver;
use App\Models\Account;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('observer persists dorf1 village resources fields runtime and hero state without extra requests', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://ts7.x1.arabics.travian.com/',
    ]);

    $html = file_get_contents(base_path('tests/Fixtures/travian-samples/dorf1.php.html'));

    app(TravianSessionResponseObserver::class)->observe($account, new SessionResponse(
        statusCode: 200,
        body: (string) $html,
        effectiveUri: 'https://ts7.x1.arabics.travian.com/dorf1.php',
        headers: [],
    ));

    $village = Village::query()->where('account_id', $account->id)->first();

    expect($village)->not->toBeNull()
        ->and($village?->travian_village_id)->toBe('23378')
        ->and($village?->resourceState?->wood)->toBe(1993)
        ->and($village?->runtimeState?->tribe_id)->toBe(1)
        ->and($village?->buildings()->whereBetween('slot_id', [1, 18])->count())->toBe(18)
        ->and($account->fresh('heroState')->heroState?->status)->toBe('returning')
        ->and($account->fresh('heroState')->heroState?->adventures_available_count)->toBe(7);
});

test('observer ignores third party responses', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://ts7.x1.arabics.travian.com/',
    ]);

    app(TravianSessionResponseObserver::class)->observe($account, new SessionResponse(
        statusCode: 200,
        body: '<html><div id="topBarHero"></div></html>',
        effectiveUri: 'https://cdn.consentmanager.net/delivery/crossdomain.html',
        headers: [],
    ));

    expect($account->fresh('heroState')->heroState)->toBeNull();
});

test('observer persists dorf2 building slots when that page was already fetched', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://ts7.x1.arabics.travian.com/',
    ]);

    $html = file_get_contents(base_path('tests/Fixtures/travian-samples/dorf2.php.html'));

    app(TravianSessionResponseObserver::class)->observe($account, new SessionResponse(
        statusCode: 200,
        body: (string) $html,
        effectiveUri: 'https://ts7.x1.arabics.travian.com/dorf2.php',
        headers: [],
    ));

    $village = Village::query()->where('account_id', $account->id)->where('travian_village_id', '23378')->first();

    expect($village)->not->toBeNull()
        ->and($village?->buildings()->whereBetween('slot_id', [19, 40])->count())->toBe(22)
        ->and($village?->buildings()->where('slot_id', 26)->first()?->building_gid)->toBe(15);
});

test('observer stores marketplace merchant availability from a fetched market page', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://ts7.x1.arabics.travian.com/',
    ]);

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

    app(TravianSessionResponseObserver::class)->observe($account, new SessionResponse(
        statusCode: 200,
        body: '<html><div id="villageName"><input class="villageInput" data-did=23378 value="AMH7"></div><div class="whereAreMyMerchants">التجار المتفرّغون: 10\10<br>تجار يعرضون الموارد للبيع في السوق: 0\10<br>تجّار على الطريق: 0\10</div></html>',
        effectiveUri: 'https://ts7.x1.arabics.travian.com/build.php?id=32&gid=17',
        headers: [],
    ));

    $resourceState = $village->fresh('resourceState')->resourceState;

    expect($resourceState?->available_merchants)->toBe(10)
        ->and($resourceState?->merchant_capacity)->toBe(500);
});

test('observer refreshes hero state from hud json while preserving known adventure count', function () {
    $account = Account::factory()->create([
        'server_url' => 'https://ts7.x1.arabics.travian.com/',
    ]);

    $account->heroState()->create([
        'status' => 'returning',
        'health_percent' => 80,
        'adventures_available_count' => 3,
        'has_unspent_attribute_points' => false,
        'seen_at' => now()->subMinutes(5),
    ]);

    app(TravianSessionResponseObserver::class)->observe($account, new SessionResponse(
        statusCode: 200,
        body: json_encode([
            'healthStatus' => 'alive',
            'health' => 91,
            'experiencePercent' => 48,
            'level' => 1,
            'levelUp' => false,
            'statusInlineIcon' => '<i class="heroHome"></i>',
            'heroStatusTitle' => '',
        ], JSON_THROW_ON_ERROR),
        effectiveUri: 'https://ts7.x1.arabics.travian.com/api/v1/hero/dataForHUD',
        headers: [],
    ));

    $heroState = $account->fresh('heroState')->heroState;

    expect($heroState?->status)->toBe('home')
        ->and($heroState?->health_percent)->toBe(91.0)
        ->and($heroState?->adventures_available_count)->toBe(3);
});
