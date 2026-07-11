# Project Handbook

## Purpose

This file is the fast handoff index for any future contributor, model, or new chat window.

Read this first when you need:

- the current project rules
- what the system already does
- what should not be broken
- where to continue next

For Travian-domain work, also activate:

- `.agents/skills/travian-automation-domain/SKILL.md`

Then read the focused docs that match the task:

- `docs/travian-domain-rules.md`
- `docs/dashboard-architecture.md`
- `docs/next-feature-troop-training.md`

## Core Product Goal

Build a multi-account Travian automation system with:

- strict account isolation
- reusable sync snapshots
- background jobs
- a dashboard that reflects the latest synced state and post-build refresh state
- per-village settings for fields, buildings, and target plans
- room for future automation, simulation, and anti-detection improvements

Current automation domains also include:

- marketplace support and manual TR transfers
- demolition through the Main Building
- hero resource support
- celebrations controls
- schedule controls with TOP / Hold behavior
- activity-log source labeling

## Current Golden Rules

1. Each account must stay transport-isolated.
   - own cookies
   - own proxy or direct connection
   - own user agent or inherited global fallback

2. The dashboard reflects the latest successful sync snapshot.
   - not fixture files
   - not guessed values
   - not local derived translations
   - immediate post-build refreshes are also valid source-of-truth updates

3. Runtime configuration should be centralized.
   - external paths and request defaults live in `config/travian.php`
   - avoid scattering hardcoded endpoints through the codebase

4. If transport identity changes, persisted cookies should not be trusted blindly.
   - `force_relogin_on_transport_change` is enabled

5. Queue workers are long-lived.
   - code changes require `php artisan queue:restart`

6. The dashboard live refresh is local only.
   - `wire:poll` re-renders from Laravel / database state
   - it does not call Travian by itself

## Current Major Building Blocks

### Dashboard

- `app/Livewire/Dashboard/Index.php`
- `app/Livewire/Dashboard/AccountRow.php`
- `app/Livewire/Dashboard/VillageRow.php`
- `app/Livewire/Dashboard/Concerns/...`
- `resources/views/livewire/dashboard/...`

Responsibilities:

- render accounts, villages, logs, row islands, and global controls
- queue sync requests
- manage bulk import and program-level settings
- manage village settings and building target plans
- show live local countdowns for construction and movement rows
- coordinate modals while keeping row-specific rendering inside row components

### Background Entry Point

- `app/Jobs/SyncTravianAccountJob.php`
- `routes/console.php`

Responsibilities:

- serve as the main queued entry point for sync + optional automation
- support full-account and single-village execution
- expose CLI commands for manual operator triggering

### Session and Transport

- `app/Application/Accounts/Session/...`
- `app/Infrastructure/Accounts/Session/Guzzle/...`

Responsibilities:

- build isolated per-account HTTP sessions
- login through Travian API flow
- load and persist cookies
- apply proxy and user-agent runtime profile

### Sync and Parsing

- `app/Application/Accounts/Sync/SyncAccountOverview.php`
- `app/Application/Accounts/Sync/Parsers/Dorf1OverviewParser.php`
- `app/Application/Accounts/Sync/Parsers/Dorf2OverviewParser.php`
- `app/Application/Accounts/Sync/PersistVillageOverview.php`

Responsibilities:

- authenticate
- fetch `dorf1.php`
- fetch `dorf2.php`
- parse village, resources, troops, movements, hero state, construction, fields, and village-center buildings
- persist the latest snapshot

### Construction Automation

- `app/Application/Accounts/Construction/RunAccountAutomation.php`
- `app/Application/Accounts/Construction/ExecuteVillageConstruction.php`
- `app/Application/Travian/TravianBuildingCatalog.php`

Responsibilities:

- consume synced village snapshots and targets
- honor tribe queue rules
  - Romans: one field queue + one building queue
  - others: one shared queue
- issue upgrade / construction orders
- refresh the affected village immediately after a successful order
- allow flexible field fallback when the highest-priority field is not currently buildable
- balance equal-priority building candidates by current level where needed
- respect stable schedule TOP keys such as `building-target:{slot}:{gid}`

### Trading And Manual Transfers

- `app/Application/Accounts/Trading/...`
- `resources/views/livewire/dashboard/partials/marketplace-transfer-modal.blade.php`

Responsibilities:

- support automatic village resources according to saved village policy
- provide TR quick send and merchant refresh behavior
- keep logs useful: successes and real failures, not repeated internal "nothing to do" noise

### Demolition

- `app/Application/Accounts/Construction/RefreshVillageDemolitionSnapshot.php`
- dashboard D panel / modal state

Responsibilities:

- read demolition state from the Main Building page
- show active countdowns
- allow canceling active demolition
- refresh snapshots after demolition or cancel flows

### Persisted State

- `app/Models/Account.php`
- `app/Models/Village.php`
- `app/Models/VillageResourceState.php`
- `app/Models/VillageRuntimeState.php`
- `app/Models/SystemSetting.php`
- `app/Models/ActivityLog.php`

Responsibilities:

- hold account identity and transport state
- hold synced village snapshots
- hold global settings
- hold operator-facing activity history

## Important Global Settings

Stored in `system_settings`:

- `automation_enabled`
- `default_user_agent`

Current behavior:

- account-specific user agent wins
- otherwise the global fallback user agent is applied

## Current Travian Flow

1. Create isolated account session.
2. Load persisted cookies if transport fingerprint still matches.
3. Request landing page.
   - currently `TRAVIAN_PATH_LANDING=/dorf1.php`
4. If already authenticated, continue.
5. Otherwise send:
   - `POST /api/v1/auth/login`
6. Extract redirect step:
   - `/api/v1/auth?code=...&response_type=redirect`
7. Request the redirect URL.
8. Persist cookies.
9. Fetch and parse `dorf1.php` and `dorf2.php`.
10. Persist resources, runtime state, field slots, building slots, and queue state.

## Current Queue Entry Points

There are currently two main operator-facing entry points:

1. Dashboard actions
   - account update
   - village update
2. CLI command
   - `php artisan travian:automation-cycle {account?}`

Both route into `SyncTravianAccountJob`.

Current behavior of that job:

1. load the target account or village
2. run `SyncAccountOverview`
3. optionally run `RunAccountAutomation`

Important:

- `travian:automation-cycle` is a manual CLI entry point
- it is not auto-scheduled inside Laravel right now

## Debug Dump Status

The old debug dump path was:

- `storage/app/private/debug/travian/<account-slug>/`

It originally existed to answer:

- what exactly did the live server return?
- did the parser fail?
- or did the response itself differ?

Current status:

- the support hook still exists conceptually
- the active implementation is currently disabled to avoid noisy file creation

## Common Debugging Traps

### Queue worker still running old code

Symptom:

- removed log lines still appear
- new code seems ignored

Fix:

```powershell
php artisan queue:restart
php artisan queue:work
```

### Encrypted payload errors

Symptom:

- `The payload is invalid.`

Likely cause:

- encrypted DB columns were manually edited into invalid plaintext

Important encrypted fields:

- `accounts.password`
- `accounts.proxy_password`
- `accounts.session_cookies`

When clearing session state manually, use `NULL`, not an empty string or random JSON string.

## Current Files Worth Reading First

1. [ARCHITECTURE.md](./ARCHITECTURE.md)
2. [PROJECT-MAP.md](./PROJECT-MAP.md)
3. [docs/travian-domain-rules.md](./docs/travian-domain-rules.md)
4. [docs/dashboard-architecture.md](./docs/dashboard-architecture.md)
5. [config/travian.php](./config/travian.php)
6. [app/Livewire/Dashboard/Index.php](./app/Livewire/Dashboard/Index.php)
7. [app/Livewire/Dashboard/AccountRow.php](./app/Livewire/Dashboard/AccountRow.php)
8. [app/Livewire/Dashboard/VillageRow.php](./app/Livewire/Dashboard/VillageRow.php)
9. [app/Application/Accounts/Sync/SyncAccountOverview.php](./app/Application/Accounts/Sync/SyncAccountOverview.php)
10. [app/Application/Accounts/Session/Actions/TravianLoginAction.php](./app/Application/Accounts/Session/Actions/TravianLoginAction.php)

## Recommended Next Build Slices

1. Add more global runtime defaults beyond fallback user agent.
2. Split `SyncAccountOverview` into smaller pipeline steps.
3. Split the sync + automation orchestration into smaller queue slices if throughput grows.
4. Add richer account runtime profiles:
   - accept-language
   - window size
   - maybe future client hints
5. Add more automation domains beyond construction:
   - training
   - send / support
   - celebrations

Before starting troop training, read:

- [docs/next-feature-troop-training.md](./docs/next-feature-troop-training.md)

## Suggested Commit Language

When the work is still building out the system, good commit wording is:

- `feat: build out global runtime settings and dashboard handoff docs`
- `feat: continue building transport defaults and project handoff guides`
