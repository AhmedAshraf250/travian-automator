<?php

namespace App\Livewire\Dashboard\Concerns;

use App\Application\Accounts\Connection\RotatesAccountProxy;
use App\Application\Accounts\Hero\RefreshAccountHeroResources;
use App\Models\Account;
use App\Models\AccountProxy;
use App\Models\AccountSetting;

trait ManagesAccountSettings
{
    /**
     * Controls the account settings modal visibility.
     */
    public bool $showAccountSettingsModal = false;

    /**
     * Stores the currently edited account identifier for the account modal.
     */
    public ?int $editingAccountId = null;

    /**
     * Stores the current account username for the account modal header.
     */
    public string $editingAccountUsername = '';

    /**
     * Stores the active account settings modal tab.
     */
    public string $accountSettingsTab = 'account';

    /**
     * Stores whether the edited account inherits the program user agent.
     */
    public bool $accountInheritUserAgentDraft = true;

    /**
     * Stores the edited account user-agent override.
     */
    public string $accountUserAgentDraft = '';

    /**
     * Stores account-level task reward collection toggle.
     */
    public bool $accountAcceptQuestsDraft = true;

    /**
     * Stores the edited proxy pool rows for the account settings modal.
     *
     * @var list<array{id:int|null, scheme:string, host:string, port:string, username:string, password:string, status:string}>
     */
    public array $accountProxyDrafts = [];

    /**
     * Stores the selected proxy row key for the account settings modal.
     */
    public string $accountActiveProxyDraft = 'direct';

    /**
     * Stores whether the edited account inherits global hero settings.
     */
    public bool $accountHeroUseGlobalSettingsDraft = true;

    /**
     * Stores account-level hero adventure toggle.
     */
    public bool $accountHeroAdventuresEnabledDraft = false;

    /**
     * Stores account-level hero minimum health.
     */
    public int $accountHeroMinHealthDraft = 40;

    /**
     * Stores account-level revive toggle.
     */
    public bool $accountHeroReviveEnabledDraft = false;

    /**
     * Stores account-level attribute upgrade toggle.
     */
    public bool $accountHeroAttributeUpgradeEnabledDraft = false;

    /**
     * Stores account-level hero attribute weights.
     *
     * @var array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     */
    public array $accountHeroAttributeWeightsDraft = [];

    /** @var array{wood: int, clay: int, iron: int, crop: int} */
    public array $accountHeroResources = [
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 0,
    ];

    public ?string $accountHeroResourcesReportedAt = null;

    public string $accountHeroResourcesMessage = '';

    /**
     * Open the per-account settings modal.
     */
    public function openAccountSettingsModal(int $accountId): void
    {
        $account = Account::query()
            ->with(['settings', 'proxies', 'heroState'])
            ->findOrFail($accountId);
        $settings = $account->settings ?? $account->settings()->create();

        $this->editingAccountId = $account->id;
        $this->editingAccountUsername = $account->username;
        $this->accountSettingsTab = 'account';
        $this->accountInheritUserAgentDraft = trim((string) $account->user_agent) === '';
        $this->accountUserAgentDraft = (string) ($account->user_agent ?? '');
        $this->accountAcceptQuestsDraft = (bool) $settings->accept_quests;
        $this->ensureLegacyAccountProxyIsPooled($account);
        app(RotatesAccountProxy::class)->refreshExpiredCooldowns($account);
        $account->refresh()->load('proxies');
        $this->accountProxyDrafts = $this->buildAccountProxyDrafts($account);
        $this->accountActiveProxyDraft = $this->resolveAccountActiveProxyDraft($account);
        $this->accountHeroUseGlobalSettingsDraft = (bool) $settings->hero_use_global_settings;
        $this->accountHeroAdventuresEnabledDraft = (bool) $settings->hero_adventures_enabled;
        $this->accountHeroMinHealthDraft = (int) ($settings->hero_min_health ?? 40);
        $this->accountHeroReviveEnabledDraft = (bool) $settings->hero_revive_enabled;
        $this->accountHeroAttributeUpgradeEnabledDraft = (bool) $settings->hero_attribute_upgrade_enabled;
        $this->accountHeroAttributeWeightsDraft = $this->normalizeHeroAttributeWeights($settings->hero_attribute_weights);
        $this->loadHeroResourceInventory($account);
        $this->showAccountSettingsModal = true;
    }

    public function refreshAccountHeroResources(RefreshAccountHeroResources $refreshAccountHeroResources): void
    {
        $this->validate([
            'editingAccountId' => ['required', 'integer', 'exists:accounts,id'],
        ]);

        $account = Account::query()->findOrFail((int) $this->editingAccountId);

        try {
            $this->accountHeroResources = $refreshAccountHeroResources->handle($account);
            $this->accountHeroResourcesReportedAt = now()->toIso8601String();
            $this->accountHeroResourcesMessage = 'Hero resources updated from Travian.';
        } catch (\Throwable $exception) {
            report($exception);
            $this->accountHeroResourcesMessage = 'Hero resources could not be updated. Check the account connection and try again.';
        }
    }

    /**
     * Close the per-account settings modal.
     */
    public function closeAccountSettingsModal(): void
    {
        $this->resetAccountSettingsState();
    }

    /**
     * Switch the account settings modal tab.
     */
    public function setAccountSettingsTab(string $tab): void
    {
        if (! in_array($tab, ['account', 'proxies', 'hero'], true)) {
            return;
        }

        $this->accountSettingsTab = $tab;
    }

    /**
     * Add one editable proxy row to the account settings modal.
     */
    public function addAccountProxyDraft(): void
    {
        $this->accountProxyDrafts[] = [
            'id' => null,
            'scheme' => 'socks5',
            'host' => '',
            'port' => '',
            'username' => '',
            'password' => '',
            'status' => AccountProxy::StatusActive,
            'failure_count' => 0,
            'lifetime_failure_count' => 0,
            'cooldown_until' => null,
            'last_error_message' => null,
        ];

        $this->accountActiveProxyDraft = 'new:'.(array_key_last($this->accountProxyDrafts) ?? 0);
    }

    /**
     * Remove one editable proxy row from the account settings modal.
     */
    public function removeAccountProxyDraft(int $index): void
    {
        if (! array_key_exists($index, $this->accountProxyDrafts)) {
            return;
        }

        $removedProxyId = $this->accountProxyDrafts[$index]['id'] ?? null;

        array_splice($this->accountProxyDrafts, $index, 1);

        if ($this->accountActiveProxyDraft === 'new:'.$index || ($removedProxyId !== null && $this->accountActiveProxyDraft === 'proxy:'.$removedProxyId)) {
            $this->accountActiveProxyDraft = 'direct';
        }
    }

    /**
     * Persist the edited account settings modal.
     */
    public function saveAccountSettings(): void
    {
        $this->validate([
            'editingAccountId' => ['required', 'integer', 'exists:accounts,id'],
            'accountInheritUserAgentDraft' => ['boolean'],
            'accountUserAgentDraft' => ['nullable', 'string', 'max:1000'],
            'accountAcceptQuestsDraft' => ['boolean'],
            'accountActiveProxyDraft' => ['required', 'string', 'max:40'],
            'accountProxyDrafts' => ['array'],
            'accountProxyDrafts.*.id' => ['nullable', 'integer'],
            'accountProxyDrafts.*.scheme' => ['required', 'string', 'in:http,https,socks4,socks4a,socks5,socks5h'],
            'accountProxyDrafts.*.host' => ['nullable', 'string', 'max:255'],
            'accountProxyDrafts.*.port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'accountProxyDrafts.*.username' => ['nullable', 'string', 'max:255'],
            'accountProxyDrafts.*.password' => ['nullable', 'string', 'max:1000'],
            'accountProxyDrafts.*.status' => ['required', 'string', 'in:active,disabled,cooldown'],
            'accountHeroUseGlobalSettingsDraft' => ['boolean'],
            'accountHeroAdventuresEnabledDraft' => ['boolean'],
            'accountHeroMinHealthDraft' => ['required', 'integer', 'min:0', 'max:100'],
            'accountHeroReviveEnabledDraft' => ['boolean'],
            'accountHeroAttributeUpgradeEnabledDraft' => ['boolean'],
            'accountHeroAttributeWeightsDraft.power' => ['required', 'integer', 'min:0', 'max:100'],
            'accountHeroAttributeWeightsDraft.offBonus' => ['required', 'integer', 'min:0', 'max:100'],
            'accountHeroAttributeWeightsDraft.defBonus' => ['required', 'integer', 'min:0', 'max:100'],
            'accountHeroAttributeWeightsDraft.productionPoints' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $account = Account::query()
            ->with(['settings', 'proxies'])
            ->findOrFail((int) $this->editingAccountId);
        $settings = $account->settings ?? $account->settings()->create();

        $account->forceFill([
            'user_agent' => $this->accountInheritUserAgentDraft
                ? null
                : trim($this->accountUserAgentDraft),
        ])->save();

        $settings->forceFill([
            'accept_quests' => $this->accountAcceptQuestsDraft,
            'hero_use_global_settings' => $this->accountHeroUseGlobalSettingsDraft,
            'hero_adventures_enabled' => $this->accountHeroAdventuresEnabledDraft,
            'hero_min_health' => max(0, min(100, (int) $this->accountHeroMinHealthDraft)),
            'hero_revive_enabled' => $this->accountHeroReviveEnabledDraft,
            'hero_attribute_upgrade_enabled' => $this->accountHeroAttributeUpgradeEnabledDraft,
            'hero_attribute_weights' => $this->normalizeHeroAttributeWeights($this->accountHeroAttributeWeightsDraft),
        ])->save();

        $this->saveAccountProxyDrafts($account);

        $this->logManualActivity($account, null, 'Account settings saved from dashboard.');
        $this->resetAccountSettingsState();

        session()->flash('dashboard-banner', "Account {$account->username}: settings were saved.");
    }

    /**
     * Build editable proxy rows for the account settings modal.
     *
     * @return list<array{id:int|null, scheme:string, host:string, port:string, username:string, password:string, status:string, failure_count:int, lifetime_failure_count:int, cooldown_until:string|null, last_error_message:string|null}>
     */
    protected function buildAccountProxyDrafts(Account $account): array
    {
        return $account->proxies
            ->map(static fn (AccountProxy $proxy): array => [
                'id' => $proxy->id,
                'scheme' => $proxy->scheme,
                'host' => $proxy->host,
                'port' => (string) $proxy->port,
                'username' => (string) ($proxy->username ?? ''),
                'password' => '',
                'status' => $proxy->status,
                'failure_count' => (int) $proxy->failure_count,
                'lifetime_failure_count' => (int) $proxy->lifetime_failure_count,
                'cooldown_until' => $proxy->cooldown_until?->toIso8601String(),
                'last_error_message' => $proxy->last_error_message,
            ])
            ->values()
            ->all();
    }

    /**
     * Resolve the selected radio value for the account proxy modal.
     */
    protected function resolveAccountActiveProxyDraft(Account $account): string
    {
        $proxyIds = $account->proxies->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
        $activeProxyId = $account->active_account_proxy_id !== null ? (int) $account->active_account_proxy_id : null;

        if ($activeProxyId !== null && in_array($activeProxyId, $proxyIds, true)) {
            return 'proxy:'.$activeProxyId;
        }

        if ($account->proxy_ip !== null && $account->proxy_port !== null) {
            $matchingProxy = $account->proxies->first(
                fn (AccountProxy $proxy): bool => $proxy->host === $account->proxy_ip
                    && (int) $proxy->port === (int) $account->proxy_port,
            );

            if ($matchingProxy instanceof AccountProxy) {
                return 'proxy:'.$matchingProxy->id;
            }
        }

        return 'direct';
    }

    /**
     * Move pre-pool proxy columns into a real pool row once.
     */
    protected function ensureLegacyAccountProxyIsPooled(Account $account): void
    {
        if ($account->proxies->isNotEmpty() || $account->proxy_ip === null || $account->proxy_port === null) {
            return;
        }

        $proxy = $account->proxies()->create([
            'scheme' => $this->normalizeProxyScheme((string) ($account->proxy_scheme ?: 'http')),
            'host' => (string) $account->proxy_ip,
            'port' => (int) $account->proxy_port,
            'username' => $account->proxy_username,
            'password' => $account->proxy_password,
            'status' => AccountProxy::StatusActive,
            'position' => 1,
        ]);

        $account->forceFill([
            'active_account_proxy_id' => $proxy->id,
        ])->save();
    }

    /**
     * Persist editable proxy rows and mirror the selected one onto the account.
     */
    protected function saveAccountProxyDrafts(Account $account): void
    {
        $existingProxies = $account->proxies()->get()->keyBy('id');
        $keptProxyIds = [];
        $selectedProxy = null;

        foreach (array_values($this->accountProxyDrafts) as $position => $draft) {
            $host = trim((string) ($draft['host'] ?? ''));
            $port = (int) ($draft['port'] ?? 0);

            if ($host === '' || $port < 1) {
                continue;
            }

            $proxy = isset($draft['id']) && $draft['id'] !== null
                ? $existingProxies->get((int) $draft['id'])
                : new AccountProxy(['account_id' => $account->id]);

            if (! $proxy instanceof AccountProxy) {
                $proxy = new AccountProxy(['account_id' => $account->id]);
            }

            $password = trim((string) ($draft['password'] ?? ''));
            $status = in_array($draft['status'] ?? AccountProxy::StatusActive, [AccountProxy::StatusActive, AccountProxy::StatusDisabled, AccountProxy::StatusCooldown], true)
                ? (string) $draft['status']
                : AccountProxy::StatusActive;

            $proxy->forceFill([
                'account_id' => $account->id,
                'scheme' => $this->normalizeProxyScheme((string) ($draft['scheme'] ?? 'http')),
                'host' => $host,
                'port' => $port,
                'username' => trim((string) ($draft['username'] ?? '')) ?: null,
                'status' => $status,
                'position' => $position + 1,
            ]);

            if ($password !== '' || ! $proxy->exists) {
                $proxy->password = $password !== '' ? $password : null;
            }

            if ($status === AccountProxy::StatusActive) {
                $proxy->cooldown_until = null;
                $proxy->failure_count = 0;
                $proxy->last_error_message = null;
            }

            $proxy->save();
            $keptProxyIds[] = (int) $proxy->id;

            if ($this->accountActiveProxyDraft === 'proxy:'.$proxy->id || $this->accountActiveProxyDraft === 'new:'.$position) {
                $selectedProxy = $proxy;
            }
        }

        $account->proxies()
            ->whereNotIn('id', $keptProxyIds === [] ? [0] : $keptProxyIds)
            ->delete();

        if (! $selectedProxy instanceof AccountProxy || $selectedProxy->status !== AccountProxy::StatusActive) {
            $selectedProxy = $account->proxies()
                ->where('status', AccountProxy::StatusActive)
                ->orderBy('position')
                ->orderBy('id')
                ->first();
        }

        if ($this->accountActiveProxyDraft === 'direct' || ! $selectedProxy instanceof AccountProxy) {
            $account->forceFill([
                'active_account_proxy_id' => null,
                'proxy_scheme' => 'http',
                'proxy_ip' => null,
                'proxy_port' => null,
                'proxy_username' => null,
                'proxy_password' => null,
                'session_cookies' => null,
                'session_transport_fingerprint' => null,
            ])->save();

            return;
        }

        $account->forceFill([
            'active_account_proxy_id' => $selectedProxy->id,
            'proxy_scheme' => $selectedProxy->scheme,
            'proxy_ip' => $selectedProxy->host,
            'proxy_port' => $selectedProxy->port,
            'proxy_username' => $selectedProxy->username,
            'proxy_password' => $selectedProxy->password,
            'session_cookies' => null,
            'session_transport_fingerprint' => null,
        ])->save();
    }

    protected function normalizeProxyScheme(string $scheme): string
    {
        return in_array($scheme, ['http', 'https', 'socks4', 'socks4a', 'socks5', 'socks5h'], true)
            ? $scheme
            : 'http';
    }

    /**
     * Reset the in-memory state used by the account settings modal.
     */
    protected function resetAccountSettingsState(): void
    {
        $this->showAccountSettingsModal = false;
        $this->editingAccountId = null;
        $this->editingAccountUsername = '';
        $this->accountSettingsTab = 'account';
        $this->accountInheritUserAgentDraft = true;
        $this->accountUserAgentDraft = '';
        $this->accountAcceptQuestsDraft = true;
        $this->accountProxyDrafts = [];
        $this->accountActiveProxyDraft = 'direct';
        $this->accountHeroUseGlobalSettingsDraft = true;
        $this->accountHeroAdventuresEnabledDraft = false;
        $this->accountHeroMinHealthDraft = 40;
        $this->accountHeroReviveEnabledDraft = false;
        $this->accountHeroAttributeUpgradeEnabledDraft = false;
        $this->accountHeroAttributeWeightsDraft = AccountSetting::defaultHeroAttributeWeights();
        $this->accountHeroResources = ['wood' => 0, 'clay' => 0, 'iron' => 0, 'crop' => 0];
        $this->accountHeroResourcesReportedAt = null;
        $this->accountHeroResourcesMessage = '';
    }

    protected function loadHeroResourceInventory(Account $account): void
    {
        $inventory = data_get($account->heroState?->payload, 'resource_inventory', []);

        $this->accountHeroResources = [
            'wood' => max(0, (int) ($inventory['wood'] ?? 0)),
            'clay' => max(0, (int) ($inventory['clay'] ?? 0)),
            'iron' => max(0, (int) ($inventory['iron'] ?? 0)),
            'crop' => max(0, (int) ($inventory['crop'] ?? 0)),
        ];
        $this->accountHeroResourcesReportedAt = isset($inventory['reported_at']) ? (string) $inventory['reported_at'] : null;
        $this->accountHeroResourcesMessage = '';
    }

    /**
     * Normalize hero attribute weights to the supported Travian attributes.
     *
     * @param  array<string, mixed>|null  $weights
     * @return array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     */
    protected function normalizeHeroAttributeWeights(?array $weights): array
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
}
