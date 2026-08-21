# Travian Automator — Project Overview

This document is a plain-English introduction to the project.  
For deeper handoff detail, also see:

- `PROJECT-HANDBOOK.md` — fast start / rules
- `PROJECT-MAP.md` — visual maps
- `ARCHITECTURE.md` — system structure
- `docs/travian-domain-rules.md` — Travian business rules
- `docs/dashboard-architecture.md` — dashboard UI boundaries

---

## What is this project?

**Travian Automator** is a **multi-account Travian automation system** built with **Laravel 13** and **Livewire 4**.

Its job is simple:

1. Manage several Travian accounts from one dashboard  
2. Sync each village’s real state from the game  
3. Run automation in the background (building, trading, demolition, etc.)  
4. Show the latest known state in the UI  

---

## What problem does it solve?

Playing Travian by hand means constantly checking villages, resources, build queues, and markets.

This app does that for you:

- Logs into Travian for each account  
- Downloads village pages (`dorf1`, `dorf2`)  
- Parses them into structured data  
- Saves snapshots in the database  
- Uses those snapshots to decide what to build or automate  
- Shows everything in a live dashboard  

---

## Core product goals

Build a multi-account Travian automation system with:

- strict account isolation  
- reusable sync snapshots  
- background jobs  
- a dashboard that reflects the latest synced state (and post-build refresh state)  
- per-village settings for fields, buildings, and target plans  
- room for future automation, simulation, and anti-detection improvements  

---

## Main features today

| Feature | What it does |
|--------|----------------|
| **Account sync** | Login, fetch pages, parse HTML, store village state |
| **Construction automation** | Upgrade fields/buildings based on targets and priorities |
| **Marketplace / trading** | Resource support and manual TR transfers |
| **Demolition** | Main Building demolish state, countdown, cancel |
| **Hero support** | Hero resource handling |
| **Celebrations** | Celebration controls |
| **Schedule (TOP / Hold)** | Control what gets priority in the plan |
| **Activity logs** | Operator-facing history of what happened |

---

## How the system runs

```text
Dashboard button  OR  CLI command
              ↓
     SyncTravianAccountJob  (queue / background)
              ↓
   1. SyncAccountOverview
      - login
      - fetch dorf1 + dorf2
      - parse
      - save snapshot to DB
              ↓
   2. RunAccountAutomation  (if automation is allowed)
      - decide construction / other automation
      - send actions to Travian
              ↓
   Dashboard reads DB and displays latest state
```

### Entry points

1. **Dashboard** — “Update now” for an account or village  
2. **CLI** — `php artisan travian:automation-cycle {account?}`  

Both go through the same job: `SyncTravianAccountJob`.

### Job behavior

1. Load the target account or village  
2. Run `SyncAccountOverview`  
3. Optionally run `RunAccountAutomation`  

Notes:

- `travian:automation-cycle` is a **manual** CLI entry point  
- It is **not** auto-scheduled inside Laravel by default  
- Dashboard live refresh (`wire:poll`) only re-reads Laravel/database state  
- Live refresh does **not** call Travian by itself  

---

## Architecture (layers)

The code is split so business logic stays clean:

| Layer | Location | Responsibility |
|-------|----------|----------------|
| **UI / Dashboard** | `app/Livewire/Dashboard/` | Screens, buttons, settings, modals |
| **Application** | `app/Application/` | Login, sync, construction, trading logic |
| **Infrastructure** | `app/Infrastructure/` | Guzzle HTTP, cookies, proxy, user-agent |
| **Models / DB** | `app/Models/` | Accounts, villages, resources, logs |
| **Jobs** | `app/Jobs/` | Background sync + automation |

So:

- **UI shows state**  
- **Application decides**  
- **Infrastructure talks to Travian**  
- **DB stores snapshots**  

This keeps the business flow independent from Guzzle, Blade, or one specific parser implementation.

---

## Main building blocks

### Dashboard

Paths:

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

### Background entry point

Paths:

- `app/Jobs/SyncTravianAccountJob.php`
- `routes/console.php`

Responsibilities:

- main queued entry point for sync + optional automation  
- support full-account and single-village execution  
- expose CLI commands for manual operator triggering  

### Session and transport

Paths:

- `app/Application/Accounts/Session/...`
- `app/Infrastructure/Accounts/Session/Guzzle/...`

Responsibilities:

- build isolated per-account HTTP sessions  
- login through Travian API flow  
- load and persist cookies  
- apply proxy and user-agent runtime profile  

### Sync and parsing

Paths:

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

### Construction automation

Paths:

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

### Trading and manual transfers

Paths:

- `app/Application/Accounts/Trading/...`
- `resources/views/livewire/dashboard/partials/marketplace-transfer-modal.blade.php`

Responsibilities:

- support automatic village resources according to saved village policy  
- provide TR quick send and merchant refresh behavior  
- keep logs useful: successes and real failures, not repeated internal “nothing to do” noise  

### Demolition

Paths:

- `app/Application/Accounts/Construction/RefreshVillageDemolitionSnapshot.php`
- dashboard D panel / modal state  

Responsibilities:

- read demolition state from the Main Building page  
- show active countdowns  
- allow canceling active demolition  
- refresh snapshots after demolition or cancel flows  

### Persisted state (models)

- `Account`
- `Village`
- `VillageResourceState`
- `VillageRuntimeState`
- `SystemSetting`
- `ActivityLog`

Responsibilities:

- hold account identity and transport state  
- hold synced village snapshots  
- hold global settings  
- hold operator-facing activity history  

---

## Current Travian flow (sync)

1. Create an isolated account session.  
2. Load persisted cookies if the transport fingerprint still matches.  
3. Request the landing page (currently `TRAVIAN_PATH_LANDING=/dorf1.php`).  
4. If already authenticated, continue.  
5. Otherwise send `POST /api/v1/auth/login`.  
6. Extract redirect step: `/api/v1/auth?code=...&response_type=redirect`.  
7. Request the redirect URL.  
8. Persist cookies.  
9. Fetch and parse `dorf1.php` and `dorf2.php`.  
10. Persist resources, runtime state, field slots, building slots, and queue state.  

---

## Critical design rules (golden rules)

1. **Account isolation**  
   Each account has its own cookies, proxy, and user-agent. Accounts must not share sessions.

2. **Source of truth = last successful sync**  
   The dashboard shows stored snapshots, not guessed values, fixture files, or local derived translations.  
   Immediate post-build refreshes are also valid source-of-truth updates.

3. **Central config**  
   Travian paths and request defaults live in `config/travian.php`.  
   Avoid scattering hardcoded endpoints through the codebase.

4. **Transport changes force re-login**  
   If proxy/user-agent identity changes, old cookies are not trusted blindly  
   (`force_relogin_on_transport_change` is enabled).

5. **Queue workers are long-lived**  
   After code changes, run `php artisan queue:restart`.

6. **Dashboard live refresh is local only**  
   `wire:poll` re-renders from Laravel / database state.  
   It does not call Travian by itself.

---

## Important global settings

Stored in `system_settings`:

- `automation_enabled`
- `default_user_agent`

Behavior:

- Account-specific user agent wins  
- Otherwise the global fallback user agent is applied  

Program-level pause can also make account/village controls look paused and non-interactive.

---

## Visual runtime flow

```text
UI (Livewire Dashboard Shell)
  ├─ AccountRow island
  ├─ VillageRow island
  └─ dispatches SyncTravianAccountJob

CLI: travian:automation-cycle
  └─ dispatches SyncTravianAccountJob

SyncTravianAccountJob
  ├─ SyncAccountOverview
  │    ├─ Account Session (cookies / proxy / UA)
  │    ├─ Guzzle transport
  │    ├─ Travian server
  │    ├─ Dorf1 + Dorf2 parsers
  │    └─ PersistVillageOverview → Database
  └─ RunAccountAutomation (optional)
       ├─ ExecuteVillageConstruction
       └─ Trading support, etc.

Database snapshots → Dashboard display
```

---

## Tech stack

| Technology | Role |
|------------|------|
| PHP 8.4 | Language |
| Laravel 13 | Application framework |
| Livewire 4 | Interactive dashboard UI |
| Pest | Tests |
| Guzzle | HTTP client to Travian |
| Tailwind CSS 4 | UI styling |
| Telescope / Pail | Debugging and monitoring |

---

## Domain docs map

For Travian-specific work, start with:

1. `.agents/skills/travian-automation-domain/SKILL.md`
2. `docs/travian-domain-rules.md`
3. `docs/dashboard-architecture.md`
4. `docs/next-feature-troop-training.md` (before adding troop training)

Important domain notes:

- `App\Application\Travian\TravianBuildingCatalog` is the building source of truth  
- Layout-unavailable buildings should usually stay visible but disabled with a reason  
- Preserve backward compatibility for persisted schedule keys  
- Avoid noisy activity logs for routine “nothing can be done” internal decisions  
- Account/program pause must make row controls visually paused and non-interactive  

---

## One-sentence summary

**Travian Automator is a control panel + background engine that logs into Travian accounts, snapshots village state, runs automation, and shows the results in a Laravel dashboard.**

---

## Where to go next

| If you want to… | Read / open… |
|-----------------|--------------|
| Understand rules and current state | `PROJECT-HANDBOOK.md` |
| See visual architecture maps | `PROJECT-MAP.md` |
| Understand folders and data flow | `ARCHITECTURE.md` |
| Change construction / Travian rules | `docs/travian-domain-rules.md` |
| Change dashboard UI structure | `docs/dashboard-architecture.md` |
| Plan troop training | `docs/next-feature-troop-training.md` |
| Inspect dashboard code | `app/Livewire/Dashboard/` |
| Inspect sync / automation code | `app/Application/Accounts/` |
| Inspect HTTP transport | `app/Infrastructure/Accounts/Session/Guzzle/` |
| Inspect background job | `app/Jobs/SyncTravianAccountJob.php` |
