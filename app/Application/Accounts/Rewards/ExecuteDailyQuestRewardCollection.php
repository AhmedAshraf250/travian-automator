<?php

namespace App\Application\Accounts\Rewards;

use App\Application\Accounts\Connection\RecordsAccountConnectionFailure;
use App\Application\Accounts\Rewards\Data\CollectableDailyQuestReward;
use App\Application\Accounts\Rewards\Parsers\DailyQuestRewardsParser;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\ActivityLog;
use JsonException;
use Throwable;

/**
 * Collects account-level daily quest rewards when an already fetched page shows their indicator.
 */
class ExecuteDailyQuestRewardCollection
{
    private const string GRAPHQL_ENDPOINT = '/api/v1/graphql';

    private const string DAILY_QUEST_AWARD_ENDPOINT = '/api/v1/daily-quest/award';

    private const string LAST_SEEN_QUERY = '{ownPlayer{dailyQuests{lastSeenAt}}}';

    private const string DAILY_QUESTS_QUERY = 'query($lastSeenAt:Int){ownPlayer{dailyQuests{achievedPoints day quests{id amountNeeded completedTimesToday isEnabled maxTimes pointsAchieved pointsPerTask nextContribution{villages resources}}resetAt rewards{id awardDescription awardRedeemed points possibleAwards{resetAt description reward{type value}}}}prevState:dailyQuests(until:$lastSeenAt){achievedPoints quests{id completedTimesToday isEnabled pointsAchieved}}}}';

    private const string REWARD_STATE_QUERY = '{ownPlayer{dailyQuests{achievedPoints rewards{points awardRedeemed}}}}';

    /**
     * Create a daily quest reward collector.
     */
    public function __construct(
        protected DailyQuestRewardsParser $dailyQuestRewardsParser,
        protected RecordsAccountConnectionFailure $recordsAccountConnectionFailure,
    ) {}

    /**
     * Execute one cautious account-level daily quest reward collection pass.
     */
    public function handle(Account $account, AccountSession $session, ?SessionResponse $sourceResponse = null): void
    {
        try {
            $account->loadMissing('settings');

            if (! $this->rewardCollectionIsEnabled($account)) {
                return;
            }

            if (! $sourceResponse instanceof SessionResponse || ! $this->dailyQuestRewardsParser->hasCollectableRewardIndicator($sourceResponse->body)) {
                return;
            }

            $referer = $sourceResponse->effectiveUri ?: $this->absoluteUri((string) config('travian.paths.overview', '/dorf1.php'), $account);
            $lastSeenResponse = $this->fetchLastSeenAt($session, $referer);

            if (! $lastSeenResponse->successful()) {
                $this->logRewardActivity($account, ActivityLogStatus::Failed, 'Daily quest reward state was rejected by Travian.', [
                    'status_code' => $lastSeenResponse->statusCode,
                    'response_body' => mb_substr($lastSeenResponse->body, 0, 500),
                ]);

                return;
            }

            $lastSeenAt = $this->dailyQuestRewardsParser->parseLastSeenAt($lastSeenResponse->body);
            $detailsResponse = $this->fetchDailyQuestDetails($session, $referer, $lastSeenAt);

            if (! $detailsResponse->successful()) {
                $this->logRewardActivity($account, ActivityLogStatus::Failed, 'Daily quest reward details were rejected by Travian.', [
                    'status_code' => $detailsResponse->statusCode,
                    'last_seen_at' => $lastSeenAt,
                    'response_body' => mb_substr($detailsResponse->body, 0, 500),
                ]);

                return;
            }

            $rewards = $this->dailyQuestRewardsParser->parseCollectableRewards($detailsResponse->body);

            if ($rewards === []) {
                $this->logRewardActivity($account, ActivityLogStatus::Pending, 'Daily quest indicator was visible, but no unlocked account rewards were parsed.', [
                    'last_seen_at' => $lastSeenAt,
                    'details_effective_uri' => $detailsResponse->effectiveUri,
                    'details_response_body' => mb_substr($detailsResponse->body, 0, 1000),
                ]);

                return;
            }

            $reward = $rewards[0];

            $this->pauseBeforeRewardCollection();

            $awardResponse = $this->collectReward($session, $reward, $referer);

            if (! $this->rewardWasAccepted($awardResponse)) {
                $this->logRewardActivity($account, ActivityLogStatus::Failed, 'Daily quest reward collection was rejected by Travian.', [
                    'reward' => $reward->toLogPayload(),
                    'status_code' => $awardResponse->statusCode,
                    'response_body' => mb_substr($awardResponse->body, 0, 500),
                ]);

                return;
            }

            $refreshResponse = $this->refreshRewardState($session, $referer);

            $this->logRewardActivity($account, ActivityLogStatus::Done, 'Daily quest reward collected successfully.', [
                'reward' => $reward->toLogPayload(),
                'remaining_collectable_rewards_count' => max(0, count($rewards) - 1),
                'refresh_status_code' => $refreshResponse?->statusCode,
            ]);
        } catch (Throwable $throwable) {
            if ($this->recordsAccountConnectionFailure->shouldBackOff($throwable)) {
                throw $throwable;
            }

            $this->logRewardActivity($account, ActivityLogStatus::Failed, 'Daily quest reward automation failed: '.$throwable->getMessage());
        }
    }

    protected function rewardCollectionIsEnabled(Account $account): bool
    {
        $settings = $account->settings;

        return $settings instanceof AccountSetting && (bool) $settings->accept_quests;
    }

    protected function fetchLastSeenAt(AccountSession $session, string $referer): SessionResponse
    {
        return $session->postJson(self::GRAPHQL_ENDPOINT, [
            'query' => self::LAST_SEEN_QUERY,
        ], $this->xhrRequestOptions($referer));
    }

    protected function fetchDailyQuestDetails(AccountSession $session, string $referer, ?int $lastSeenAt): SessionResponse
    {
        return $session->postJson(self::GRAPHQL_ENDPOINT, [
            'query' => self::DAILY_QUESTS_QUERY,
            'variables' => [
                'lastSeenAt' => $lastSeenAt,
            ],
        ], $this->xhrRequestOptions($referer));
    }

    protected function collectReward(AccountSession $session, CollectableDailyQuestReward $reward, string $referer): SessionResponse
    {
        return $session->postJson(
            self::DAILY_QUEST_AWARD_ENDPOINT,
            $reward->collectionPayload(),
            $this->xhrRequestOptions($referer),
        );
    }

    protected function refreshRewardState(AccountSession $session, string $referer): ?SessionResponse
    {
        try {
            return $session->postJson(self::GRAPHQL_ENDPOINT, [
                'query' => self::REWARD_STATE_QUERY,
            ], $this->xhrRequestOptions($referer));
        } catch (Throwable) {
            return null;
        }
    }

    protected function rewardWasAccepted(SessionResponse $response): bool
    {
        if (! $response->successful()) {
            return false;
        }

        if (trim($response->body) === '') {
            return true;
        }

        try {
            $payload = json_decode($response->body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return true;
        }

        return ! is_array($payload) || (($payload['success'] ?? true) === true && ! isset($payload['error']));
    }

    protected function pauseBeforeRewardCollection(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        usleep(random_int(1_200_000, 3_600_000));
    }

    /**
     * @return array<string, mixed>
     */
    protected function xhrRequestOptions(?string $referer = null): array
    {
        $headers = [
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json; charset=UTF-8',
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
    protected function logRewardActivity(Account $account, ActivityLogStatus $status, string $message, array $payload = []): void
    {
        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => null,
            'activity_type' => ActivityType::Quest,
            'status' => $status,
            'payload' => $payload,
            'message' => $message,
            'executed_at' => now(),
        ]);
    }
}
