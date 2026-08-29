# Dashboard Architecture

This file explains the current dashboard structure and where future changes should live.

## Component Boundaries

- `App\Livewire\Dashboard\Index` is the dashboard shell. Keep it focused on page-level state, modal coordination, and shared actions.
- Dashboard behavior is split into concerns under `app/Livewire/Dashboard/Concerns/`. Add behavior to the closest existing concern before expanding `Index`.
- `App\Livewire\Dashboard\Account\Row` owns account-row display and account-level controls.
- `App\Livewire\Dashboard\Village\Row` owns village-row display and village-level controls.
- Stateful child components are grouped by owner and their views mirror the class namespace, such as `Dashboard\Village\TroopOrders` and `livewire.dashboard.village.troop-orders`.
- Passive Blade fragments with no Livewire class live under `resources/views/livewire/dashboard/partials/`.

## Performance Direction

- Prefer Livewire child components/islands for rows that can update independently.
- Do not load heavy modal data until the modal is opened.
- Avoid querying from Blade. Prepare view data in PHP.
- Keep polling narrow. Poll only what must stay fresh, and prefer event/job-driven refreshes when a specific action changes state.
- Loading indicators should block duplicate clicks while Livewire is processing, but should stay visually light.

## Dashboard UX Decisions

- Account rows should be visible by default when the program opens and an account is connected. Users can collapse villages afterward.
- Program pause applies the same pause language and visual state as account pause.
- Account pause should make village quick controls (`F`, `B`, `S`, `R`, `C`, `T`, `TR`, `D`) yellow and non-interactive.
- Account headers should be visually distinct from village bodies without becoming too dark or detached from the design.
- The activity log should use familiar window-like controls and a compact header.
- Activity log height should be resizable in a natural way, preferably by dragging its upper boundary.

## Modal Rules

- Settings modals should generally remain open after save when the user is configuring several related controls.
- Close modals explicitly with `X` unless a flow is clearly a one-shot command.
- Immediate UI toggles inside tabs should update dependent fields immediately, not only after saving and reopening.
- Trading settings and the TR panel should use the same vocabulary and explain the difference:
  - saved policy for automatic support,
  - quick send and merchant refresh for one-off TR actions.

## Row Controls

- `B` shows building automation and schedule controls.
- `S` shows schedule order controls; `TOP` is a temporary priority override and `Hold` blocks a candidate.
- `R` should open a small explanatory menu for hero resources rather than toggling directly.
- `C` should open a compact celebrations control menu rather than toggling blindly.
- `TR` is marketplace quick send/refresh.
- `D` is demolition.

## Adding New Features

- Put domain rules in an application service or catalog first.
- Add parser code close to existing Travian parsing services.
- Add Livewire actions in a focused concern or child component.
- Add Blade only after the PHP state shape is clear.
- Add tests around the domain behavior before relying on manual dashboard checks.
