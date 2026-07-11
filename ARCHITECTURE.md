# Travian Automator Architecture

## Purpose

This document explains the current project structure, the responsibility of each major part, how data flows through the system, and what to watch while debugging or extending the project.

It is intentionally simple and practical.

For detailed Travian business rules, read:

- [docs/travian-domain-rules.md](./docs/travian-domain-rules.md)

For dashboard component boundaries, read:

- [docs/dashboard-architecture.md](./docs/dashboard-architecture.md)

## Current Architectural Style

The project currently follows a layered Laravel structure with clear separation between:

- `Application`: use cases and orchestration
- `Infrastructure`: external integrations and transport details
- `Models`: persisted state
- `Livewire`: dashboard interaction and screen state
- `Jobs`: background execution
- `Enums` and `Data`: structured value objects and states
- project skills and docs: persistent handoff context for future sessions

This is a good direction for long-term growth because the business flow does not directly depend on Guzzle, Blade, or one parser implementation.

## Main Folders

### `app/Application`

Contains business use cases and orchestration logic.

Examples:

- `Accounts/Session/Actions/TravianLoginAction.php`
  - Responsible for authenticating one account against Travian
- `Accounts/Session/Contracts/AccountSession.php`
  - Contract for isolated account transport
- `Accounts/Session/Contracts/AccountSessionFactory.php`
  - Contract for creating an isolated session per account
- `Accounts/Sync/SyncAccountOverview.php`
  - Main sync use case that logs in, downloads `dorf1` + `dorf2`, parses them, and stores the snapshot
- `Accounts/Sync/Parsers/Dorf1OverviewParser.php`
  - Parses raw HTML into structured data objects
- `Accounts/Sync/Parsers/Dorf2OverviewParser.php`
  - Parses village-center building slots
- `Accounts/Sync/PersistVillageOverview.php`
  - Writes parsed village snapshots into the database
- `Accounts/Construction/RunAccountAutomation.php`
  - Runs one automation pass for all eligible villages in one account
- `Accounts/Construction/ExecuteVillageConstruction.php`
  - Decides and issues one construction action per available queue

### `app/Infrastructure`

Contains framework- or library-specific implementations.

Examples:

- `Accounts/Session/Guzzle/GuzzleAccountSessionFactory.php`
  - Builds isolated Guzzle clients
- `Accounts/Session/Guzzle/GuzzleAccountSession.php`
  - Actual HTTP transport implementation

Rule:

- if one day Guzzle is replaced, most of the project should remain unchanged

### `app/Jobs`

Contains background work.

Example:

- `SyncTravianAccountJob.php`
  - queues and runs account sync plus optional automation in the background

### `app/Livewire`

Contains dashboard state and actions.

Examples:

- `Dashboard/Index.php`
  - dashboard shell and modal coordination
  - queues sync jobs
  - coordinates shared dashboard state
- `Dashboard/AccountRow.php`
  - account row island
  - account-level controls and display state
- `Dashboard/VillageRow.php`
  - village row island
  - village-level quick controls and display state
- `Dashboard/Concerns/...`
  - focused action groups used by the dashboard shell

### `app/Models`

Contains persisted state and relationships.

Examples:

- `Account`
- `Village`
- `VillageResourceState`
- `VillageRuntimeState`
- `ActivityLog`

### `resources/views/livewire`

Contains dashboard presentation only.

Examples:

- account row
- village row
- activity log panel
- import modal
- row views and modal partials

The Blade files should display state, not perform sync logic.

### `routes/console.php`

Contains operator-facing Artisan commands.

Examples:

- `travian:sync-account {account}`
  - runs sync directly
- `travian:automation-cycle {account?}`
  - dispatches the same background job used by the dashboard

## Current Runtime Flow

### Account overview sync + automation cycle

1. User clicks `Update now` in the dashboard
2. `app/Livewire/Dashboard/Index.php` dispatches `SyncTravianAccountJob`
3. `SyncTravianAccountJob` calls `SyncAccountOverview`
4. If allowed, the same job later calls `RunAccountAutomation`
5. `SyncAccountOverview` asks `AccountSessionFactory` for an isolated session
6. `GuzzleAccountSessionFactory` creates a dedicated client with:
   - own cookies
   - own proxy
   - own user agent
7. `TravianLoginAction` authenticates the account
8. `SyncAccountOverview` downloads `/dorf1.php` and `/dorf2.php`
9. `Dorf1OverviewParser` and `Dorf2OverviewParser` parse:
   - village identity
   - resources
   - troop snapshot
   - movements
   - hero status
   - construction queue
   - field slots
   - village-center building slots
10. `PersistVillageOverview` stores the snapshot in database tables
11. `RunAccountAutomation` evaluates the stored village state and target plans
12. `ExecuteVillageConstruction` issues one field / building action per allowed queue
13. After a successful build request, the affected village is refreshed immediately from Travian
14. Dashboard reads the stored snapshot and renders it

### Manual CLI cycle

1. Operator runs `php artisan travian:automation-cycle`
2. The command selects one or more active, non-archived accounts
3. It dispatches `SyncTravianAccountJob` for each account

Important:

- this command is available for manual triggering
- it is not currently auto-scheduled inside Laravel

### Dashboard live refresh

The dashboard uses Livewire refresh patterns to re-render from the database. Keep polling as narrow as possible.

Important:

- this is an internal Laravel request
- it does not call Travian by itself
- it only reflects already-persisted state
- row islands should reduce payload and keep unrelated UI from rerendering unnecessarily

## Source of Truth

Current rule:

- the dashboard reflects the latest successful sync snapshot or immediate post-build refresh snapshot

This means:

- if the server returns Arabic names, the dashboard stores and shows Arabic names
- if the server returns English names, the dashboard stores and shows English names
- local fixture files inside `may-help` do not drive the live dashboard

## Who Talks to Whom

### High-level dependency map

`Livewire Dashboard` or `CLI Command`
-> `SyncTravianAccountJob`
-> `SyncAccountOverview` and optional `RunAccountAutomation`
-> `Session Contract`
-> `Infrastructure Transport`
-> `Travian Server`
-> `Parsers`
-> `Models / Database`
-> `Dashboard View`

### Important boundaries

- `Livewire` should not know Guzzle details
- `Parser` should not save directly to database
- `Infrastructure` should not contain UI logic
- `Blade` should not invent business state

## Why This Is Expandable

The architecture is currently extensible because:

1. Transport is abstracted behind `AccountSession`
2. Login is isolated in `TravianLoginAction`
3. Parsing is isolated in parser classes
4. Snapshot persistence is isolated in `PersistVillageOverview`
5. Background work is already job-based
6. Dashboard mostly consumes persisted snapshots
7. Account isolation is per-session, not shared globally

## How To Check If It Is Still Expandable

Use this checklist before adding major features.

### Good signs

- a new feature can be added as a new action, parser, or job without rewriting the dashboard
- changing HTTP transport does not force rewriting sync logic
- adding a new village snapshot field does not require touching every layer
- tests can be written with fake sessions instead of real network

### Warning signs

- one class starts doing login, parsing, saving, formatting, and scheduling together
- Blade starts deriving too much business state
- Guzzle details leak into application logic
- one huge parser begins handling too many unrelated pages
- jobs become tightly coupled to one specific UI flow

## Honest Current Weak Points

The direction is good, but a few parts will eventually need another refactor:

1. `SyncAccountOverview` is becoming a large orchestration class
   - later it can be split into smaller pipeline steps
2. `Dorf1OverviewParser` is growing
   - later split into smaller extractors:
     - village meta
     - resources
     - movements
     - troops
     - construction
3. `SyncTravianAccountJob` currently orchestrates both sync and automation in one queued entry point
   - later we may split this into dedicated sync and execution jobs if throughput grows
4. Dashboard view formatting is still evolving fast
   - presentational view models may help later
   - modal data should stay lazy where possible
5. We still need a clearer strategy for simulation between syncs
6. More domain rules should move into explicit catalogs and services as features grow

These are normal growth points, not architectural failure.

## Debugging Guide

### 1. Activity log

Start from the dashboard activity log.

Look for:

- `pending`
- `running`
- `done`
- `failed`

### 2. Queue worker

If the dashboard does not change, verify the worker is running:

- `php artisan queue:work`

If code changed but old behavior remains:

- restart the worker

### 3. Debug HTML dumps

When debug dumping is enabled, raw live `dorf1` snapshots are stored under:

- `storage/app/private/debug/travian/<account-slug>/`

Use them when you need to answer:

- what exactly did the server return?
- did the parser fail?
- or did the server response itself differ?

Current note:

- the dump hook is present conceptually
- the active implementation is currently turned off to avoid noisy files during normal operation

### 4. Tests

Important distinction:

- live sync uses the real Travian response
- tests use fixed local fixture files

So test dumps and live dumps are not the same thing.

### 5. Why there is a `marshal` debug folder

That folder is created by tests.

The feature tests create a fake account with username `marshal`, so the debug path becomes:

- `storage/app/private/debug/travian/marshal`

This does not mean a real user account exists with that name.

## Data Responsibilities

### `accounts`

Stores account-level connectivity and lifecycle state:

- server
- credentials
- proxy
- user agent
- encrypted cookies
- current sync state

### `villages`

Stores stable village identity:

- village id
- name
- coordinates
- population

### `village_resource_states`

Stores latest synced resource snapshot:

- current resources
- production per hour
- capacities
- sync timestamps

### `village_runtime_states`

Stores latest runtime snapshot:

- troop slots
- incoming and outgoing movements
- hero state
- construction entries

### `activity_logs`

Stores operational timeline:

- what happened
- when
- for which account / village
- status and message

## Extension Strategy

When adding a new capability, try to place it in one of these categories.

### New page parser

Examples:

- rally point
- reports

Recommended path:

- new parser class
- new sync action or sub-action
- persist new snapshot state

### New automation decision

Examples:

- auto build
- auto train
- auto resource transfer

Recommended path:

- separate application action
- queueable job
- consume existing snapshots
- write result to activity log
- avoid noisy "nothing to do" logs for ordinary blocked policy decisions

### New UI block

Recommended path:

- add data to existing view model / loaded relations
- keep Blade dumb
- avoid direct parser logic in the view

## Can This Become a Service Later

Yes.

There are two different future models:

### 1. Hosted service

You host the app and workers yourself.

Users:

- create accounts inside your system
- subscribe
- get access through your platform

This is the cleanest model technically.

### 2. Licensed self-hosted product

You distribute the app, and each customer activates it with:

- license key
- token
- subscription key

This is also possible, but weaker than hosted service because client-side code is always easier to inspect and patch.

## Recommended Strategy For Productization

If the long-term goal is commercial control, prefer this order:

1. keep the automation core server-side
2. make users authenticate against your service
3. issue short-lived signed tokens or license sessions
4. gate premium features server-side
5. keep billing, licensing, anti-abuse, and orchestration private

## Token / License Model

A practical design could be:

1. Customer purchases subscription
2. Your licensing service creates:
   - customer id
   - plan
   - seat count
   - expiration
3. App validates with your licensing API
4. API returns signed token or activation session
5. App periodically re-validates
6. Expired or blocked subscriptions lose access

Optional additions:

- machine binding
- domain binding
- per-seat limits
- feature flags
- remote kill switch

## Public Repo vs Private Logic

If the repository is public, do not rely on secrecy of code for business protection.

Instead keep private:

- `.env` and all secrets
- billing logic
- licensing service
- internal admin tools
- monitoring and abuse detection
- private automation heuristics if needed

In many real systems, the public repo is not the product.
The product value is:

- hosted infrastructure
- managed updates
- support
- billing
- central control
- private services behind APIs

## What Should Stay Out Of Git

Always keep ignored:

- `may-help`
- debug dumps
- local raw captures
- real credentials
- cookies
- tokens
- CA bundle paths specific to one machine

## Practical Next Step For Future SaaS Direction

When the sync core becomes stable, the next architectural slice should be:

1. add `licenses` and `subscriptions` domain tables
2. add a lightweight license validation service layer
3. gate dashboard and workers behind license state
4. separate customer identity from game account identity

That will prepare the project for turning into a real product instead of only a local automation tool.
