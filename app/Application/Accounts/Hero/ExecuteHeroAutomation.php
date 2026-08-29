<?php

namespace App\Application\Accounts\Hero;

use App\Application\Accounts\Hero\Data\ParsedHeroState;
use App\Application\Accounts\Hero\Parsers\HeroAdventurePageAnalyzer;
use App\Application\Accounts\Hero\Parsers\HeroAttributesAnalyzer;
use App\Application\Accounts\Hero\Parsers\HeroHudDataParser;
use App\Application\Accounts\Hero\Parsers\HeroTopBarParser;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\AccountHeroState;
use App\Models\AccountSetting;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Throwable;

/**
 * Executes one safe account-level hero automation pass.
 */
class ExecuteHeroAutomation
{
    /**
     * Create a new hero automation executor.
     */
    public function __construct(
        protected HeroTopBarParser $heroTopBarParser,
        protected HeroHudDataParser $heroHudDataParser,
        protected HeroAdventurePageAnalyzer $heroAdventurePageAnalyzer,
        protected HeroAttributesAnalyzer $heroAttributesAnalyzer,
    ) {}

    /**
     * Execute one hero automation pass for the account.
     */
    public function handle(Account $account, AccountSession $session): void
    {
        $account = Account::query()
            ->with(['settings', 'heroState'])
            ->findOrFail($account->id);

        $settings = $this->resolveHeroSettings($account);

        if (
            ! $settings['adventures_enabled']
            && ! $settings['revive_enabled']
            && ! $settings['attribute_upgrade_enabled']
        ) {
            return;
        }

        $heroState = $this->currentHeroState($account, $session);

        if (! $heroState instanceof AccountHeroState) {
            return;
        }

        if ($settings['revive_enabled'] && $heroState->status === 'dead') {
            $this->executeRevive($account, $session, $heroState);

            return;
        }

        if ($settings['attribute_upgrade_enabled'] && $heroState->has_unspent_attribute_points) {
            $this->executeAttributeDistribution($account, $session, $settings);
            $account->refresh()->load('heroState');
            $heroState = $account->heroState ?? $heroState;
        }

        if (! $settings['adventures_enabled']) {
            return;
        }

        $this->executeAdventureIfAvailable($account, $session, $settings, $heroState);
    }

    /**
     * Resolve effective hero settings for an account.
     *
     * @return array{
     *     adventures_enabled: bool,
     *     min_health: int,
     *     revive_enabled: bool,
     *     attribute_upgrade_enabled: bool,
     *     attribute_weights: array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     * }
     */
    protected function resolveHeroSettings(Account $account): array
    {
        $settings = $account->settings ?? $account->settings()->create();

        if ((bool) $settings->hero_use_global_settings) {
            return SystemSetting::heroDefaults();
        }

        return [
            'adventures_enabled' => (bool) $settings->hero_adventures_enabled,
            'min_health' => max(0, min(100, (int) ($settings->hero_min_health ?? 40))),
            'revive_enabled' => (bool) $settings->hero_revive_enabled,
            'attribute_upgrade_enabled' => (bool) $settings->hero_attribute_upgrade_enabled,
            'attribute_weights' => $this->normalizeAttributeWeights($settings->hero_attribute_weights),
        ];
    }

    /**
     * Resolve the latest known hero state, refreshing Travian when a saved timer is due.
     */
    protected function currentHeroState(Account $account, AccountSession $session): ?AccountHeroState
    {
        if ($account->heroState instanceof AccountHeroState && ! $this->heroStateTimerIsDue($account->heroState)) {
            return $account->heroState;
        }

        if ($account->heroState instanceof AccountHeroState) {
            $hudState = $this->refreshFromHud($account, $session, (string) config('travian.paths.overview', '/dorf1.php'));

            if ($hudState instanceof ParsedHeroState) {
                return $account->fresh('heroState')->heroState;
            }
        }

        $response = $session->get(
            (string) config('travian.paths.overview', '/dorf1.php'),
            $this->documentRequestOptions($this->absoluteUri('/', $account)),
        );

        if (! $response->successful()) {
            return null;
        }

        $heroState = $this->heroTopBarParser->parse($response->body);

        if (! $heroState instanceof ParsedHeroState) {
            return null;
        }

        $this->persistHeroState($account, $heroState);

        return $account->fresh('heroState')->heroState;
    }

    /**
     * Determine whether an account-level hero timer has elapsed and needs confirmation from Travian.
     */
    protected function heroStateTimerIsDue(AccountHeroState $heroState): bool
    {
        if (! in_array($heroState->status, ['adventure', 'returning', 'regenerating'], true)) {
            return false;
        }

        if ($heroState->hero_remaining_seconds === null) {
            return false;
        }

        if ($heroState->seen_at === null) {
            return true;
        }

        $elapsedSeconds = max(0, now()->getTimestamp() - $heroState->seen_at->getTimestamp());

        return $elapsedSeconds >= (int) $heroState->hero_remaining_seconds;
    }

    /**
     * Try to revive a dead hero with village resources.
     */
    protected function executeRevive(Account $account, AccountSession $session, AccountHeroState $heroState): void
    {
        $heroPage = $session->get('/hero', $this->documentRequestOptions($this->absoluteUri((string) config('travian.paths.overview', '/dorf1.php'), $account)));
        $attributes = $session->get('/api/v1/hero/v2/screen/attributes', $this->xhrRequestOptions($heroPage->effectiveUri));
        $analysis = $this->heroAttributesAnalyzer->analyze($attributes->body);

        if ($analysis === null) {
            $this->logHeroActivity($account, ActivityLogStatus::Failed, 'Could not read hero attributes before revive.', [
                'action' => 'revive',
            ]);

            return;
        }

        if ($analysis->heroState instanceof ParsedHeroState) {
            $this->persistHeroState($account, $analysis->heroState);
        }

        if (! $analysis->canReviveWithResources) {
            $this->logHeroActivity($account, ActivityLogStatus::Pending, 'Hero revive is waiting for resources or free crop.', [
                'action' => 'revive',
                'blocked_reason' => 'resources_or_crop',
                'blocked_message' => $analysis->reviveBlockedMessage,
                'required_resources' => $analysis->reviveRequiredResources,
                'duration_seconds' => $analysis->reviveDurationSeconds,
                'duration_label' => $analysis->reviveDurationLabel,
                'previous_status' => $heroState->status,
            ]);

            return;
        }

        $response = $session->postJson('/api/v1/hero/v2/revive', [
            'action' => 'heroRevive',
        ], $this->xhrRequestOptions($this->absoluteUri('/hero/attributes', $account)));

        if (! $response->successful()) {
            $this->logHeroActivity($account, ActivityLogStatus::Failed, 'Hero revive request was rejected by Travian.', [
                'action' => 'revive',
                'status_code' => $response->statusCode,
            ]);

            return;
        }

        $this->refreshFromAttributes($account, $session, '/hero/attributes');
        $this->refreshFromHud($account, $session, '/hero/attributes');
        $this->logHeroActivity($account, ActivityLogStatus::Done, 'Hero revive order issued successfully.', [
            'action' => 'revive',
            'required_resources' => $analysis->reviveRequiredResources,
            'duration_seconds' => $analysis->reviveDurationSeconds,
            'duration_label' => $analysis->reviveDurationLabel,
        ]);
    }

    /**
     * Distribute free hero points according to configured fixed weights.
     *
     * @param  array<string, mixed>  $settings
     */
    protected function executeAttributeDistribution(Account $account, AccountSession $session, array $settings): void
    {
        $heroPage = $session->get('/hero', $this->documentRequestOptions($this->absoluteUri((string) config('travian.paths.overview', '/dorf1.php'), $account)));
        $attributes = $session->get('/api/v1/hero/v2/screen/attributes', $this->xhrRequestOptions($heroPage->effectiveUri));
        $analysis = $this->heroAttributesAnalyzer->analyze($attributes->body);

        if ($analysis === null) {
            $this->logHeroActivity($account, ActivityLogStatus::Failed, 'Could not read hero attributes before point distribution.', [
                'action' => 'attributes',
            ]);

            return;
        }

        if ($analysis->heroState instanceof ParsedHeroState) {
            $this->persistHeroState($account, $analysis->heroState);
        }

        $payload = $this->heroAttributesAnalyzer->buildDistributionPayload(
            $settings['attribute_weights'],
            $analysis->freePoints,
        );

        if ($payload === []) {
            return;
        }

        $response = $session->postJson('/api/v1/hero/v2/attributes', [
            'attributes' => $payload,
        ], $this->xhrRequestOptions($this->absoluteUri('/hero/attributes', $account)));

        if (! $response->successful()) {
            $this->logHeroActivity($account, ActivityLogStatus::Failed, 'Hero attribute distribution request was rejected by Travian.', [
                'action' => 'attributes',
                'status_code' => $response->statusCode,
                'attributes' => $payload,
                'response_body' => mb_substr($response->body, 0, 500),
            ]);

            return;
        }

        $this->refreshFromHud($account, $session, '/hero/attributes');
        $this->logHeroActivity($account, ActivityLogStatus::Done, 'Hero attribute points distributed successfully.', [
            'action' => 'attributes',
            'free_points' => $analysis->freePoints,
            'attributes' => $payload,
        ]);
    }

    /**
     * Send the hero to the first visible adventure when settings allow it.
     *
     * @param  array<string, mixed>  $settings
     */
    protected function executeAdventureIfAvailable(
        Account $account,
        AccountSession $session,
        array $settings,
        AccountHeroState $heroState,
    ): void {
        if ($heroState->status !== 'home') {
            return;
        }

        if ((float) ($heroState->health_percent ?? 0) < (int) $settings['min_health']) {
            $this->logHeroActivity($account, ActivityLogStatus::Pending, 'Hero adventure blocked by health threshold.', [
                'action' => 'adventure',
                'health_percent' => $heroState->health_percent,
                'min_health' => $settings['min_health'],
            ]);

            return;
        }

        $heroStateSource = $heroState->payload['source'] ?? null;

        if ((int) $heroState->adventures_available_count <= 0 && $heroStateSource !== 'data_for_hud') {
            return;
        }

        $adventurePage = $session->get('/hero/adventures', $this->documentRequestOptions($this->absoluteUri((string) config('travian.paths.overview', '/dorf1.php'), $account)));
        $analysis = $this->heroAdventurePageAnalyzer->analyze($adventurePage->body);

        if ($analysis->heroState instanceof ParsedHeroState) {
            $this->persistHeroState($account, $analysis->heroState);
        }

        $adventure = $analysis->firstAdventure();

        if ($adventure === null || $adventure->number <= 0) {
            $this->logHeroActivity($account, ActivityLogStatus::Pending, 'No actionable hero adventure id was parsed from the page.', [
                'action' => 'adventure',
                'available_count' => $heroState->adventures_available_count,
            ]);

            return;
        }

        $sendPayload = [
            'action' => 'troopsSend',
            'eventType' => 50,
            'troops' => [
                ['t11' => 1],
            ],
            'target' => [
                'adventureId' => $adventure->number,
            ],
        ];

        $previewResponse = $session->putJson('/api/v1/troop/send', $sendPayload, $this->xhrRequestOptions($adventurePage->effectiveUri));

        if (! $previewResponse->successful()) {
            $this->logHeroActivity($account, ActivityLogStatus::Failed, 'Hero adventure send preview was rejected by Travian.', [
                'action' => 'adventure',
                'status_code' => $previewResponse->statusCode,
                'adventure_id' => $adventure->number,
                'response_body' => mb_substr($previewResponse->body, 0, 500),
            ]);

            return;
        }

        $confirmationNonce = $this->nonceFromResponse($previewResponse);

        if ($confirmationNonce === null) {
            $this->logHeroActivity($account, ActivityLogStatus::Failed, 'Hero adventure confirmation nonce was not returned by Travian.', [
                'action' => 'adventure',
                'adventure_id' => $adventure->number,
                'response_headers' => $previewResponse->headers,
                'response_body' => mb_substr($previewResponse->body, 0, 500),
            ]);

            return;
        }

        $response = $session->postJson(
            '/api/v1/troop/send',
            $sendPayload,
            $this->xhrRequestOptions($adventurePage->effectiveUri, ['X-Nonce' => $confirmationNonce]),
        );

        if (! $response->successful()) {
            $this->logHeroActivity($account, ActivityLogStatus::Failed, 'Hero adventure send confirmation was rejected by Travian.', [
                'action' => 'adventure',
                'status_code' => $response->statusCode,
                'adventure_id' => $adventure->number,
                'response_body' => mb_substr($response->body, 0, 500),
            ]);

            return;
        }

        $arrivalIn = $this->extractAdventureArrivalSeconds($response->body);
        $hudState = $this->refreshFromHud($account, $session, '/hero/adventures');

        if (! $hudState instanceof ParsedHeroState || ! in_array($hudState->status, ['adventure', 'returning'], true)) {
            $this->logHeroActivity($account, ActivityLogStatus::Failed, 'Hero adventure send was accepted but not confirmed by HUD.', [
                'action' => 'adventure',
                'adventure_id' => $adventure->number,
                'arrival_in' => $arrivalIn,
                'hud_status' => $hudState?->status,
                'hud_payload' => $hudState?->payload,
                'response_body' => mb_substr($response->body, 0, 500),
            ]);

            return;
        }

        $this->logHeroActivity($account, ActivityLogStatus::Done, 'Hero sent to adventure successfully.', [
            'action' => 'adventure',
            'adventure_id' => $adventure->number,
            'place' => $adventure->place,
            'difficulty' => $adventure->difficulty,
            'traveling_duration' => $adventure->travelingDuration,
            'arrival_in' => $arrivalIn,
        ]);
    }

    /**
     * Refresh the account hero snapshot from the HUD API.
     */
    protected function refreshFromHud(Account $account, AccountSession $session, string $referer): ?ParsedHeroState
    {
        try {
            $response = $session->get('/api/v1/hero/dataForHUD', $this->xhrRequestOptions($this->absoluteUri($referer, $account)));
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $heroState = $this->heroHudDataParser->parse($response->body);

        if ($heroState instanceof ParsedHeroState) {
            $this->persistHeroState($account, $heroState);
        }

        return $heroState;
    }

    /**
     * Refresh the account hero snapshot from the hero attributes API.
     */
    protected function refreshFromAttributes(Account $account, AccountSession $session, string $referer): ?ParsedHeroState
    {
        try {
            $response = $session->get('/api/v1/hero/v2/screen/attributes', $this->xhrRequestOptions($this->absoluteUri($referer, $account)));
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $analysis = $this->heroAttributesAnalyzer->analyze($response->body);
        $heroState = $analysis?->heroState;

        if ($heroState instanceof ParsedHeroState) {
            $this->persistHeroState($account, $heroState);
        }

        return $heroState;
    }

    /**
     * Persist a parsed hero state onto the account.
     */
    protected function persistHeroState(Account $account, ParsedHeroState $heroState): void
    {
        $source = $heroState->payload['source'] ?? null;
        $shouldPersistAdventureCount = in_array($source, ['top_bar', 'adventure_view_data'], true);
        $values = [
            'status' => $heroState->status,
            'home_village_travian_id' => $heroState->homeVillageTravianId,
            'payload' => $heroState->payload,
            'seen_at' => now(),
        ];

        if ($heroState->heroRemainingSeconds !== null || in_array($heroState->status, ['home', 'dead'], true)) {
            $values['hero_remaining_seconds'] = $heroState->heroRemainingSeconds;
        }

        if ($shouldPersistAdventureCount) {
            $values['adventures_available_count'] = $heroState->adventuresAvailableCount;
        }

        if ($source !== 'adventure_view_data') {
            $values = [
                ...$values,
                'has_unspent_attribute_points' => $heroState->hasUnspentAttributePoints,
                'unspent_attribute_points' => $heroState->unspentAttributePoints,
            ];
        }

        foreach ([
            'health_percent' => $heroState->healthPercent,
            'experience_percent' => $heroState->experiencePercent,
            'level' => $heroState->level,
        ] as $key => $value) {
            if ($value !== null) {
                $values[$key] = $value;
            }
        }

        AccountHeroState::query()->updateOrCreate(['account_id' => $account->id], $values);
    }

    /**
     * Log one hero automation event.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function logHeroActivity(Account $account, ActivityLogStatus $status, string $message, array $payload = []): void
    {
        ActivityLog::query()->create([
            'account_id' => $account->id,
            'activity_type' => ActivityType::Hero,
            'status' => $status,
            'payload' => $payload,
            'message' => $message,
            'executed_at' => now(),
        ]);
    }

    /**
     * Build headers for top-level document navigation requests.
     *
     * @return array<string, mixed>
     */
    protected function documentRequestOptions(?string $referer = null): array
    {
        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
        ];

        if ($referer !== null && $referer !== '') {
            $headers['Referer'] = $referer;
        }

        return ['headers' => $headers];
    }

    /**
     * Build headers for Travian XHR API requests.
     *
     * @return array<string, mixed>
     */
    protected function nonceFromResponse(SessionResponse $response): ?string
    {
        foreach ($response->headers as $header => $values) {
            if (mb_strtolower($header) !== 'x-nonce') {
                continue;
            }

            $nonce = trim((string) ($values[0] ?? ''));

            return $nonce !== '' ? $nonce : null;
        }

        return null;
    }

    /**
     * @param  array<string, string>  $extraHeaders
     * @return array<string, mixed>
     */
    protected function xhrRequestOptions(?string $referer = null, array $extraHeaders = []): array
    {
        $headers = [
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        if ($referer !== null && $referer !== '') {
            $headers['Referer'] = $referer;
        }

        $headers = [
            ...$headers,
            ...$extraHeaders,
        ];

        return ['headers' => $headers];
    }

    /**
     * Normalize account-level attribute weight input.
     *
     * @param  array<string, mixed>|null  $weights
     * @return array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     */
    protected function normalizeAttributeWeights(?array $weights): array
    {
        $defaults = AccountSetting::defaultHeroAttributeWeights();

        if (! is_array($weights)) {
            return $defaults;
        }

        return [
            'power' => max(0, (int) ($weights['power'] ?? $defaults['power'])),
            'offBonus' => max(0, (int) ($weights['offBonus'] ?? $defaults['offBonus'])),
            'defBonus' => max(0, (int) ($weights['defBonus'] ?? $defaults['defBonus'])),
            'productionPoints' => max(0, (int) ($weights['productionPoints'] ?? $defaults['productionPoints'])),
        ];
    }

    /**
     * Extract the arrival countdown from the troop send response.
     */
    protected function extractAdventureArrivalSeconds(string $json): ?int
    {
        $payload = json_decode($json, true);

        if (! is_array($payload) || ! is_array($payload['troops'] ?? null)) {
            return null;
        }

        $firstTroop = $payload['troops'][0] ?? null;

        if (! is_array($firstTroop) || ! isset($firstTroop['arrivalIn'])) {
            return null;
        }

        return (int) $firstTroop['arrivalIn'];
    }

    /**
     * Build an absolute URI from the account server base.
     */
    protected function absoluteUri(string $uri, Account $account): string
    {
        if (preg_match('/^https?:\/\//i', $uri) === 1) {
            return $uri;
        }

        return rtrim($account->server_url, '/').'/'.ltrim($uri, '/');
    }
}
