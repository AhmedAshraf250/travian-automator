<?php

namespace App\Application\Accounts\Session;

use App\Application\Accounts\Hero\Data\ParsedHeroState;
use App\Application\Accounts\Hero\Parsers\HeroAttributesAnalyzer;
use App\Application\Accounts\Hero\Parsers\HeroHudDataParser;
use App\Application\Accounts\Hero\Parsers\HeroTopBarParser;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Sync\Parsers\Dorf1OverviewParser;
use App\Application\Accounts\Sync\Parsers\Dorf2OverviewParser;
use App\Application\Accounts\Sync\PersistVillageOverview;
use App\Models\Account;
use App\Models\AccountHeroState;
use Throwable;

/**
 * Updates local snapshots opportunistically from Travian responses already requested.
 */
class TravianSessionResponseObserver
{
    /**
     * Create a response observer.
     */
    public function __construct(
        protected HeroTopBarParser $heroTopBarParser,
        protected HeroHudDataParser $heroHudDataParser,
        protected HeroAttributesAnalyzer $heroAttributesAnalyzer,
        protected Dorf1OverviewParser $dorf1OverviewParser,
        protected Dorf2OverviewParser $dorf2OverviewParser,
        protected PersistVillageOverview $persistVillageOverview,
    ) {}

    /**
     * Observe a successful response and persist any account state visible in it.
     */
    public function observe(Account $account, SessionResponse $response): void
    {
        if (! $response->successful() || ! $this->isSameTravianHost($account, $response->effectiveUri)) {
            return;
        }

        $path = (string) (parse_url($response->effectiveUri, PHP_URL_PATH) ?: '');

        if (str_contains($path, '/api/v1/hero/dataForHUD')) {
            $this->persistHeroState($account, $this->heroHudDataParser->parse($response->body));

            return;
        }

        if (str_contains($path, '/api/v1/hero/v2/screen/attributes')) {
            $analysis = $this->heroAttributesAnalyzer->analyze($response->body);
            $this->persistHeroState($account, $analysis?->heroState);

            return;
        }

        if ($this->looksLikeHtml($response->body)) {
            $this->observeHtmlDocument($account, $response, $path);
        }
    }

    /**
     * Observe a Travian HTML document.
     */
    protected function observeHtmlDocument(Account $account, SessionResponse $response, string $path): void
    {
        $this->persistHeroState($account, $this->heroTopBarParser->parse($response->body));

        if (! str_contains($path, '/dorf1.php')) {
            if (str_contains($path, '/dorf2.php')) {
                $this->observeDorf2Document($account, $response);
            }

            return;
        }

        try {
            $dorf1Overview = $this->dorf1OverviewParser->parse($response->body);
        } catch (Throwable) {
            return;
        }

        $village = $account->villages()->firstOrNew([
            'travian_village_id' => $dorf1Overview->activeVillage->travianVillageId,
        ]);

        $this->persistVillageOverview->handleDorf1Only($village, $dorf1Overview);
    }

    /**
     * Observe a dorf2 document for building slots when the active village can be identified.
     */
    protected function observeDorf2Document(Account $account, SessionResponse $response): void
    {
        $travianVillageId = $this->extractActiveVillageId($response->body);

        if ($travianVillageId === null) {
            return;
        }

        try {
            $dorf2Overview = $this->dorf2OverviewParser->parse($response->body);
        } catch (Throwable) {
            return;
        }

        $village = $account->villages()->firstOrNew([
            'travian_village_id' => $travianVillageId,
        ]);

        $this->persistVillageOverview->handleDorf2Only($village, $dorf2Overview);
    }

    /**
     * Persist an account-level hero snapshot when one was visible.
     */
    protected function persistHeroState(Account $account, ?ParsedHeroState $heroState): void
    {
        if (! $heroState instanceof ParsedHeroState) {
            return;
        }

        $source = $heroState->payload['source'] ?? null;
        $values = [
            'status' => $heroState->status,
            'home_village_travian_id' => $heroState->homeVillageTravianId,
            'payload' => $heroState->payload,
            'seen_at' => now(),
        ];

        if ($heroState->heroRemainingSeconds !== null || in_array($heroState->status, ['home', 'dead'], true)) {
            $values['hero_remaining_seconds'] = $heroState->heroRemainingSeconds;
        }

        if (in_array($source, ['top_bar', 'adventure_view_data'], true)) {
            $values['adventures_available_count'] = $heroState->adventuresAvailableCount;
        }

        if ($source !== 'adventure_view_data') {
            $values['has_unspent_attribute_points'] = $heroState->hasUnspentAttributePoints;
            $values['unspent_attribute_points'] = $heroState->unspentAttributePoints;
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
     * Avoid observing third-party CDN/consent responses.
     */
    protected function isSameTravianHost(Account $account, string $effectiveUri): bool
    {
        $accountHost = parse_url($account->server_url, PHP_URL_HOST);
        $responseHost = parse_url($effectiveUri, PHP_URL_HOST);

        return is_string($accountHost)
            && is_string($responseHost)
            && strcasecmp($accountHost, $responseHost) === 0;
    }

    /**
     * Detect documents cheaply before attempting DOM parsing.
     */
    protected function looksLikeHtml(string $body): bool
    {
        return str_contains($body, '<html')
            || str_contains($body, '<!DOCTYPE html')
            || str_contains($body, 'id="topBarHero"')
            || str_contains($body, "id='topBarHero'");
    }

    /**
     * Extract the active village id from the standard village name input.
     */
    protected function extractActiveVillageId(string $html): ?string
    {
        if (preg_match('/id=["\']villageName["\'][\s\S]*?data-did=["\']([^"\']+)["\']/u', $html, $matches) !== 1) {
            return null;
        }

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
