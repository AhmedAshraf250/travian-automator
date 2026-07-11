# Travian Domain Rules

This file captures product decisions for the Travian automator so a future session can continue without rediscovering the same rules.

## Source Of Truth

- Building ids, names, categories, aliases, requirements, max levels, default targets, and duplicate rules belong in `App\Application\Travian\TravianBuildingCatalog`.
- UI and automation code should ask the catalog instead of hard-coding Arabic names or ids.
- Travian sample responses under `may-help/travian-samples/` are evidence for parsers and flows. Keep new samples there when a feature depends on Travian HTML.

## Building Levels And Defaults

- Most buildings max at level 20 unless the catalog says otherwise.
- Resource bonus buildings max at level 5:
  - Sawmill / `معمل النجارة`
  - Brickyard / `مصنع البلوك`
  - Iron Foundry / `مصنع الحديد`
  - Grain Mill / `المطاحن`
  - Bakery / `المخابز`
- Cranny / `المخبأ` maxes at level 10.
- Warehouse / `المخزن` and Granary / `مخزن الحبوب` default to active construction targets when discovered.
- Main Building / `المبنى الرئيسي` is a protected/default building and currently defaults to target level 14 unless the user changes it.
- Rally Point and tribe wall slots default to target level 1.
- A `max` badge in layouts means the current building reached its known final level, not merely the user's selected target.

## Layout Rules

- Slots 19-40 are village building layout slots.
- Fixed slots are special and should look visually distinct:
  - 26: Main Building
  - 39: Rally Point
  - 40: Tribe wall, according to tribe
- Existing occupied slots should be visually clearer than empty slots.
- The layout table header (`Place ID`, `Current`, `Building`, `Max level`, `Priority`, `Active`) should remain sticky while scrolling.
- The `Current` column owns the main building icon. Avoid repeated decorative icons in both current and selected-building columns.
- The building selector should show building icons beside names when practical.
- When the user selects a building in an empty slot, initialize `Max level` to 1 unless a stronger default rule applies.
- When the user changes an unbuilt selected slot back to `Empty`, reset `Max level` to 0 on save.
- Buildings with missing requirements should usually remain visible as disabled choices with a short reason, rather than being hidden.
- Great Warehouse and Great Granary require actual Wonder of the World presence; requirement level `0` still means the building must exist.

## Duplicate Building Rules

- Most buildings must not be duplicated in one village layout.
- Residence and Palace are mutually exclusive in practical village planning and should not both be freely duplicated.
- These buildings are exceptions and can have more than one copy only after at least one existing copy reached its final level:
  - Warehouse / `المخزن`
  - Granary / `مخزن الحبوب`
  - Cranny / `المخبأ`
- If no copy reached final level, do not allow adding another copy of those exception buildings.

## Construction Planning

- Lower numeric priority means higher priority.
- Priority remains the main ordering rule.
- When two building candidates have equal priority, prefer the lower current level first so buildings like Warehouse and Granary can advance together instead of one racing ahead.
- Schedule `TOP` is a temporary user override and must survive the next level of the same building target. Prefer stable keys like `building-target:{slot}:{gid}`.
- Legacy persisted keys such as `building:{slot}:{nextLevel}` should keep working when possible.
- `Hold` should block that schedule candidate until the user releases it.
- If the Main Building was destroyed, the system should keep a repair target in layouts and allow it to be promoted until it reaches the selected target.
- If construction is blocked because storage is too low, prefer raising Warehouse/Granary according to the layout policy instead of spamming blocked candidate logs.

## Marketplace And Hero Resources

- Marketplace support should log successful sends and real errors.
- Routine internal decisions such as "no eligible supplier under current policy/resources" should not repeatedly spam the activity log.
- Manual transfer success logs should include resource amount/type and destination coordinates/name when available.
- Hero resources are a safety lever for construction. When crop production is negative, keep a crop reserve before spending hero resources so troops do not immediately starve.

## Demolition

- Demolition requires Main Building level 10 or higher.
- The D panel should show active demolition countdowns and allow canceling them.
- Active demolition state comes from the Main Building page countdown element.
- Cancel flows may redirect through multiple Travian responses; refresh the snapshot after cancel so the dashboard reflects the game.

## Activity Logs

- Logs should make the source understandable:
  - `TRAVIAN` when there is evidence of a real Travian request/response.
  - `APP` when it is an internal scheduler, policy, or dashboard decision.
- Avoid repeating low-value pending logs every automation tick.
- Logs should favor compact, actionable text over noisy implementation detail.
