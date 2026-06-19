<?php

namespace App\Application\Accounts\Celebrations;

use App\Application\Accounts\Celebrations\Data\ParsedCelebrationOption;
use App\Application\Accounts\Celebrations\Parsers\TownHallCelebrationPageParser;
use App\Application\Accounts\Connection\RecordsAccountConnectionFailure;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Enums\VillageCelebrationType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use App\Models\VillageSetting;
use Throwable;

/**
 * Executes one village celebration when the configured rules allow it.
 */
class ExecuteVillageCelebration
{
    /**
     * Create a new village celebration executor instance.
     */
    public function __construct(
        protected TownHallCelebrationPageParser $townHallCelebrationPageParser,
        protected RecordsAccountConnectionFailure $recordsAccountConnectionFailure,
    ) {}

    /**
     * Execute the next allowed celebration for one village.
     */
    public function handle(Account $account, Village $village, AccountSession $session): void
    {
        try {
            if (! $village->is_active) {
                return;
            }

            $settings = $village->settings;

            if (! $settings instanceof VillageSetting || ! $settings->celebration_enabled) {
                return;
            }

            $townHallSlot = $village->buildings
                ->first(fn ($building): bool => (int) $building->building_gid === 24 && (int) $building->slot_id >= 19 && (int) $building->slot_id <= 40);

            if ($townHallSlot === null) {
                return;
            }

            $townHallPageUri = (string) config('travian.paths.build', '/build.php')
                .'?id='.(int) $townHallSlot->slot_id
                .'&gid=24';

            $townHallPage = $session->get(
                $townHallPageUri,
                $this->documentRequestOptions($this->absoluteUri((string) config('travian.paths.village_center', '/dorf2.php'), $account)),
            );

            $parsedPage = $this->townHallCelebrationPageParser->parse($townHallPage->body);

            if ($parsedPage->hasRunningCelebration) {
                return;
            }

            $selectedOption = $this->selectCelebrationOption(
                options: $parsedPage->options,
                preferredType: $settings->celebration_type instanceof VillageCelebrationType
                    ? $settings->celebration_type
                    : VillageSetting::defaultCelebrationType(),
                minimumCulturePoints: max(0, (int) $settings->celebration_min_culture_points),
            );

            if (! $selectedOption instanceof ParsedCelebrationOption || $selectedOption->actionUri === null) {
                return;
            }

            $response = $session->get(
                $selectedOption->actionUri,
                $this->documentRequestOptions($townHallPage->effectiveUri),
            );

            ActivityLog::query()->create([
                'account_id' => $account->id,
                'village_id' => $village->id,
                'activity_type' => ActivityType::Celebration,
                'status' => ActivityLogStatus::Done,
                'payload' => [
                    'type' => $selectedOption->type->value,
                    'culture_points' => $selectedOption->culturePoints,
                    'action_uri' => $selectedOption->actionUri,
                ],
                'result' => [
                    'status_code' => $response->statusCode,
                    'effective_uri' => $response->effectiveUri,
                ],
                'message' => ucfirst($selectedOption->type->value).' celebration started successfully.',
                'executed_at' => now(),
            ]);
        } catch (Throwable $throwable) {
            if ($this->recordsAccountConnectionFailure->shouldBackOff($throwable)) {
                throw $throwable;
            }

            ActivityLog::query()->create([
                'account_id' => $account->id,
                'village_id' => $village->id,
                'activity_type' => ActivityType::Celebration,
                'status' => ActivityLogStatus::Failed,
                'message' => 'Village celebration automation failed: '.$throwable->getMessage(),
                'executed_at' => now(),
            ]);
        }
    }

    /**
     * Select the best matching celebration option for the current village settings.
     *
     * @param  list<ParsedCelebrationOption>  $options
     */
    protected function selectCelebrationOption(array $options, VillageCelebrationType $preferredType, int $minimumCulturePoints): ?ParsedCelebrationOption
    {
        $optionsByType = [];

        foreach ($options as $option) {
            $optionsByType[$option->type->value] = $option;
        }

        $preferredOrder = match ($preferredType) {
            VillageCelebrationType::Great => [VillageCelebrationType::Great, VillageCelebrationType::Small],
            VillageCelebrationType::Small => [VillageCelebrationType::Small, VillageCelebrationType::Great],
        };

        foreach ($preferredOrder as $candidateType) {
            $option = $optionsByType[$candidateType->value] ?? null;

            if (! $option instanceof ParsedCelebrationOption) {
                continue;
            }

            if ($option->culturePoints < $minimumCulturePoints) {
                continue;
            }

            return $option;
        }

        return null;
    }

    /**
     * Build headers for document navigation requests.
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

        return [
            'headers' => $headers,
            'allow_redirects' => [
                'max' => 5,
                'strict' => false,
                'referer' => true,
                'protocols' => ['http', 'https'],
                'track_redirects' => false,
            ],
        ];
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
