<?php

namespace App\Application\Accounts\Rewards;

use App\Application\Accounts\Connection\RecordsAccountConnectionFailure;
use App\Application\Accounts\Rewards\Data\CollectableQuestReward;
use App\Application\Accounts\Rewards\Parsers\QuestRewardsParser;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\ActivityLog;
use App\Models\Village;
use JsonException;
use Throwable;

/**
 * Collects visible progressive task rewards for the currently selected Travian village.
 */
class ExecuteQuestRewardCollection
{
    private const int MAX_REWARDS_PER_PASS = 3;

    /**
     * Create a progressive task reward collector.
     */
    public function __construct(
        protected QuestRewardsParser $questRewardsParser,
        protected RecordsAccountConnectionFailure $recordsAccountConnectionFailure,
    ) {}

    /**
     * Execute one cautious reward collection pass.
     */
    public function handle(Account $account, Village $village, AccountSession $session, ?SessionResponse $sourceResponse = null): void
    {
        try {
            $account->loadMissing('settings');

            if (! $this->rewardCollectionIsEnabled($account)) {
                return;
            }

            if (! $sourceResponse instanceof SessionResponse || ! $this->questRewardsParser->hasCollectableRewardIndicator($sourceResponse->body)) {
                return;
            }

            $tasksResponse = $session->get(
                (string) config('travian.paths.tasks', '/tasks?t=village'),
                $this->documentRequestOptions($sourceResponse?->effectiveUri ?? $this->absoluteUri((string) config('travian.paths.overview', '/dorf1.php'), $account)),
            );

            if (! $tasksResponse->successful()) {
                $this->logRewardActivity($account, $village, ActivityLogStatus::Failed, 'Quest rewards page was rejected by Travian.', [
                    'status_code' => $tasksResponse->statusCode,
                ]);

                return;
            }

            $rewards = $this->questRewardsParser->parseCollectableRewards($tasksResponse->body);

            if ($rewards === []) {
                $this->logRewardActivity($account, $village, ActivityLogStatus::Pending, 'Quest reward indicator was visible, but no collectable rewards were parsed from the tasks page.', [
                    'tasks_effective_uri' => $tasksResponse->effectiveUri,
                    'tasks_response_body' => mb_substr($tasksResponse->body, 0, 1000),
                ]);

                return;
            }

            $collectedCount = 0;
            $tasksReferer = $tasksResponse->effectiveUri;

            while ($rewards !== [] && $collectedCount < self::MAX_REWARDS_PER_PASS) {
                $reward = $rewards[0];

                if ($collectedCount > 0) {
                    $this->pauseBetweenRewardCollections();
                }

                $collectResponse = $this->collectReward($session, $reward, $tasksReferer);

                if (! $this->rewardWasAccepted($collectResponse)) {
                    $this->logRewardActivity($account, $village, ActivityLogStatus::Failed, 'Quest reward collection was rejected by Travian.', [
                        'reward' => $this->rewardPayload($reward),
                        'status_code' => $collectResponse->statusCode,
                        'response_body' => mb_substr($collectResponse->body, 0, 500),
                    ]);

                    return;
                }

                $this->refreshHeroHud($session, $tasksReferer);
                $reloadResponse = $this->reloadTasks($session, $tasksReferer);
                $rewards = $reloadResponse->successful()
                    ? $this->questRewardsParser->parseCollectableRewards($reloadResponse->body)
                    : [];
                $collectedCount++;

                $this->logRewardActivity($account, $village, ActivityLogStatus::Done, 'Quest reward collected successfully.', [
                    'reward' => $this->rewardPayload($reward),
                    'remaining_rewards_count' => count($rewards),
                    'reload_status_code' => $reloadResponse->statusCode,
                ]);
            }
        } catch (Throwable $throwable) {
            if ($this->recordsAccountConnectionFailure->shouldBackOff($throwable)) {
                throw $throwable;
            }

            $this->logRewardActivity($account, $village, ActivityLogStatus::Failed, 'Quest reward automation failed: '.$throwable->getMessage());
        }
    }

    /**
     * React to a dorf1 response that was already requested by another automation flow.
     */
    public function handleObservedDorf1Response(Account $account, AccountSession $session, SessionResponse $sourceResponse): void
    {
        if (! $sourceResponse->successful()) {
            return;
        }

        $path = (string) (parse_url($sourceResponse->effectiveUri, PHP_URL_PATH) ?: '');

        if (! str_contains($path, '/dorf1.php') || ! $this->questRewardsParser->hasCollectableRewardIndicator($sourceResponse->body)) {
            return;
        }

        $village = $this->resolveVillageFromDorf1Response($account, $sourceResponse);

        if (! $village instanceof Village) {
            return;
        }

        $this->handle($account, $village, $session, $sourceResponse);
    }

    protected function rewardCollectionIsEnabled(Account $account): bool
    {
        $settings = $account->settings;

        return $settings instanceof AccountSetting && (bool) $settings->accept_quests;
    }

    protected function resolveVillageFromDorf1Response(Account $account, SessionResponse $sourceResponse): ?Village
    {
        $travianVillageId = $this->extractActiveVillageId($sourceResponse->body);

        if ($travianVillageId === null) {
            return null;
        }

        return $account->villages()->firstWhere('travian_village_id', $travianVillageId);
    }

    protected function extractActiveVillageId(string $html): ?string
    {
        if (preg_match('/id=["\']villageName["\'][\s\S]*?data-did=["\']([^"\']+)["\']/u', $html, $matches) !== 1) {
            return null;
        }

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function collectReward(AccountSession $session, CollectableQuestReward $reward, string $referer): SessionResponse
    {
        return $session->postJson(
            '/api/v1/progressive-tasks/collectReward',
            $reward->collectionPayload(),
            $this->xhrRequestOptions($referer),
        );
    }

    protected function refreshHeroHud(AccountSession $session, string $referer): ?SessionResponse
    {
        try {
            return $session->get('/api/v1/hero/dataForHUD', $this->xhrRequestOptions($referer));
        } catch (Throwable) {
            return null;
        }
    }

    protected function reloadTasks(AccountSession $session, string $referer): SessionResponse
    {
        return $session->get('/api/v1/progressive-tasks/reload', $this->xhrRequestOptions($referer));
    }

    protected function rewardWasAccepted(SessionResponse $response): bool
    {
        if (! $response->successful()) {
            return false;
        }

        try {
            $payload = json_decode($response->body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return true;
        }

        return ! is_array($payload) || ($payload['success'] ?? true) === true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rewardPayload(CollectableQuestReward $reward): array
    {
        return [
            'task_name' => $reward->taskName,
            'quest_id' => $reward->questId,
            'group' => $reward->group,
            'level_title' => $reward->levelTitle,
            'reward_values' => $reward->rewardValues,
            'request_payload' => $reward->collectionPayload(),
        ];
    }

    protected function pauseBetweenRewardCollections(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        usleep(random_int(1_600_000, 4_200_000));
    }

    /**
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
     * @return array<string, mixed>
     */
    protected function xhrRequestOptions(?string $referer = null): array
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

        return ['headers' => $headers];
    }

    protected function absoluteUri(string $uri, Account $account): string
    {
        if (preg_match('/^https?:\/\//i', $uri) === 1) {
            return $uri;
        }

        return rtrim($account->server_url, '/').'/'.ltrim($uri, '/');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function logRewardActivity(Account $account, Village $village, ActivityLogStatus $status, string $message, array $payload = []): void
    {
        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Quest,
            'status' => $status,
            'payload' => $payload,
            'message' => $message,
            'executed_at' => now(),
        ]);
    }
}
