# Next Feature: Troop Training

This is the planning note for adding troop-training automation without disrupting construction, trading, or dashboard performance.

## Goal

Add a controlled way to train troops per village, using the existing village `Troops Training` tab and the row `T` control.

## Expected Scope

- Village-level enable/disable for troop training.
- A per-village training policy:
  - selected troop types,
  - target counts or batch sizes,
  - minimum resource reserve,
  - optional crop safety limit,
  - priority between troop types.
- A row `T` menu that summarizes current policy and lets the user enable/disable training quickly.
- Activity logs only for issued training orders, real Travian rejections, and useful blockers.

## Domain Catalog Needed

Create or extend a catalog for troop data by tribe:

- Roman, Teuton, Gaul troop ids/names/icons.
- Training building:
  - Barracks
  - Stable
  - Workshop
  - optionally special buildings if later supported.
- Resource cost, crop consumption, base training time, and prerequisites where needed.

Keep this data out of Blade.

## Suggested Architecture

- Application layer service:
  - `PlanVillageTroopTraining`
  - `ExecuteVillageTroopTraining`
  - parser/snapshot service for training pages.
- Livewire:
  - Keep page shell coordination in `Dashboard\Index`.
  - Put village training actions in a dedicated dashboard concern or `VillageRow` action if it is row-local.
  - Keep modal/tab state lazy so it loads only when the user opens Troops Training.
- Persistence:
  - Check existing tables/models before adding new ones.
  - Use clear names like `village_troop_training_settings` if a new table is needed.

## Safety Rules

- Respect program pause, account pause, and village pause.
- Do not train if crop reserve is unsafe.
- Do not spend resources reserved for critical construction unless the user explicitly allows it.
- Do not spam logs when current resources or queue state make training impossible under the saved policy.
- If Travian rejects a training order, log the response context and avoid immediate repeat attempts.

## Parser Evidence

Before implementation, collect Travian samples for:

- Barracks page with available troops.
- Stable page with available troops.
- Workshop page with available troops.
- Not-enough-resources state.
- Queue already running state.
- Successful training request and redirect.

Store them under `may-help/travian-samples/` with task-specific names.

## Test Checklist

- Settings save and reopen correctly.
- Disabled training never issues orders.
- Pause states block training.
- Crop reserve blocks unsafe training.
- Successful planner chooses the correct building and troop.
- Rejected Travian response does not create a retry loop.
- Dashboard row `T` summary matches saved policy.
