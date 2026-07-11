---
name: travian-automation-domain
description: Use when working on this Travian automator's domain logic, dashboard UX, construction planning, village layouts, marketplace transfers, hero resources, demolition, activity logs, schedule TOP/Hold behavior, pause/resume behavior, or future troop-training automation.
---

# Travian Automation Domain

Use this project skill before changing Travian-specific behavior. It preserves the product decisions that are easy to forget in a new chat.

## Start Here

Read these files before implementation:

- `docs/travian-domain-rules.md` for building, construction, resource, log, and Travian business rules.
- `docs/dashboard-architecture.md` for Livewire component boundaries and dashboard UI conventions.
- `docs/next-feature-troop-training.md` before adding troop training.

Then inspect the current code around the requested feature. Prefer existing services, concerns, components, and tests over adding parallel patterns.

## Guardrails

- `App\Application\Travian\TravianBuildingCatalog` is the building source of truth. Do not hard-code Arabic labels or building ids in UI code when the catalog can answer it.
- Layout unavailable buildings should usually stay visible but disabled with a reason, not disappear.
- Preserve backward compatibility for persisted schedule keys when introducing better keys.
- Avoid noisy activity logs for routine "nothing can be done" internal decisions.
- Account/program pause must make row controls visually paused and non-interactive.
- For equal construction priority, balance repeated upgrades by current level where the domain rules require it.

## Verification

For PHP changes, run Pint. For behavior changes, run the relevant Pest tests or the full compact suite when the blast radius is broad.
