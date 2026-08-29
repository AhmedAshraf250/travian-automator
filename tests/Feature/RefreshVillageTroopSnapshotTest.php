<?php

use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Troops\RefreshVillageTroopSnapshot;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('fresh military pages recognize researched guards and scout and prefer Smithy level evidence', function () {
    $capturesRoot = base_path('may-help/travian-samples/task17');

    if (! is_dir($capturesRoot)) {
        $this->markTestSkipped('The local task17 Travian captures are not available.');
    }

    $account = Account::factory()->create();
    $village = $account->villages()->create([
        'travian_village_id' => '12345',
        'name' => 'Roman Village',
        'is_active' => true,
    ]);
    $village->runtimeState()->create(['tribe_id' => 1, 'server_reported_at' => now()]);

    foreach ([19 => 34, 20 => 38, 22 => 39, 13 => 35] as $gid => $slot) {
        $village->buildings()->create([
            'slot_id' => $slot,
            'building_gid' => $gid,
            'building_type' => 'Military building',
            'current_level' => 5,
        ]);
    }

    $session = new class($capturesRoot) implements AccountSession
    {
        public function __construct(private string $capturesRoot) {}

        public function get(string $uri, array $options = []): SessionResponse
        {
            $path = match (true) {
                str_contains($uri, 'gid=19') => '/barracks/new-samp01.md',
                str_contains($uri, 'gid=20') => '/Stable/samp01.md',
                str_contains($uri, 'gid=22') => '/Academy/samp1.md',
                str_contains($uri, 'gid=13') => '/Smithy/new-samp01.md',
                default => throw new RuntimeException("Unexpected URI {$uri}"),
            };

            return new SessionResponse(200, (string) file_get_contents($this->capturesRoot.$path), 'https://example.com'.$uri, []);
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected.');
        }

        public function putJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('putJson was not expected.');
        }

        public function persist(): void {}
    };

    $state = app(RefreshVillageTroopSnapshot::class)->handle($account, $village, $session);
    $units = $state->snapshot->units;

    expect(data_get($units, '2.research_state'))->toBe('researched')
        ->and(data_get($units, '2.training.available'))->toBeTrue()
        ->and(data_get($units, '4.research_state'))->toBe('researched')
        ->and(data_get($units, '4.training.available'))->toBeTrue()
        ->and(data_get($units, '1.smithy.current_level'))->toBe(1)
        ->and(data_get($units, '1.smithy.resource_shortage'))->toBeTrue()
        ->and(data_get($units, '4.smithy.resource_shortage'))->toBeFalse();
});
