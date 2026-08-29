<?php

namespace App\Application\Travian;

/**
 * Canonical metadata for the first eight trainable units of each supported tribe.
 */
final class TravianTroopCatalog
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private const array DEFINITIONS = [
        1 => ['tribe_id' => 1, 'tribe_slot' => 1, 'english' => 'Legionnaire', 'arabic' => 'الجندي الأول', 'training_building_gid' => 19, 'training_supported' => true, 'initially_unlocked' => true, 'research_requirements' => [], 'training_cost' => ['wood' => 120, 'clay' => 100, 'iron' => 150, 'crop' => 30], 'crop_upkeep' => 1],
        2 => ['tribe_id' => 1, 'tribe_slot' => 2, 'english' => 'Praetorian', 'arabic' => 'حارس الإمبراطور', 'training_building_gid' => 19, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 1], ['gid' => 13, 'level' => 1]], 'training_cost' => ['wood' => 100, 'clay' => 130, 'iron' => 160, 'crop' => 70], 'crop_upkeep' => 1],
        3 => ['tribe_id' => 1, 'tribe_slot' => 3, 'english' => 'Imperian', 'arabic' => 'الجندي المهاجم', 'training_building_gid' => 19, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 5], ['gid' => 13, 'level' => 1]], 'training_cost' => ['wood' => 150, 'clay' => 160, 'iron' => 210, 'crop' => 80], 'crop_upkeep' => 1],
        4 => ['tribe_id' => 1, 'tribe_slot' => 4, 'english' => 'Equites Legati', 'arabic' => 'ضابط التجسس', 'training_building_gid' => 20, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 20, 'level' => 1], ['gid' => 22, 'level' => 5]], 'training_cost' => ['wood' => 140, 'clay' => 160, 'iron' => 20, 'crop' => 40], 'crop_upkeep' => 2],
        5 => ['tribe_id' => 1, 'tribe_slot' => 5, 'english' => 'Equites Imperatoris', 'arabic' => 'الفارس المهاجم', 'training_building_gid' => 20, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 20, 'level' => 5], ['gid' => 22, 'level' => 5]], 'training_cost' => ['wood' => 550, 'clay' => 440, 'iron' => 320, 'crop' => 100], 'crop_upkeep' => 3],
        6 => ['tribe_id' => 1, 'tribe_slot' => 6, 'english' => 'Equites Caesaris', 'arabic' => 'فارس القيصر', 'training_building_gid' => 20, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 20, 'level' => 10], ['gid' => 22, 'level' => 15]], 'training_cost' => ['wood' => 550, 'clay' => 640, 'iron' => 800, 'crop' => 180], 'crop_upkeep' => 4],
        7 => ['tribe_id' => 1, 'tribe_slot' => 7, 'english' => 'Battering Ram', 'arabic' => 'محطمة الأبواب', 'training_building_gid' => 21, 'training_supported' => false, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 10], ['gid' => 21, 'level' => 1]], 'training_cost' => ['wood' => 900, 'clay' => 360, 'iron' => 500, 'crop' => 70], 'crop_upkeep' => 3],
        8 => ['tribe_id' => 1, 'tribe_slot' => 8, 'english' => 'Fire Catapult', 'arabic' => 'المنجنيق الناري', 'training_building_gid' => 21, 'training_supported' => false, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 21, 'level' => 10], ['gid' => 22, 'level' => 15]], 'training_cost' => ['wood' => 950, 'clay' => 1350, 'iron' => 600, 'crop' => 90], 'crop_upkeep' => 6],
        11 => ['tribe_id' => 2, 'tribe_slot' => 1, 'english' => 'Maceman', 'arabic' => 'المهاجم بهراوة', 'training_building_gid' => 19, 'training_supported' => true, 'initially_unlocked' => true, 'research_requirements' => [], 'training_cost' => ['wood' => 95, 'clay' => 75, 'iron' => 40, 'crop' => 40], 'crop_upkeep' => 1],
        12 => ['tribe_id' => 2, 'tribe_slot' => 2, 'english' => 'Spearman', 'arabic' => 'مقاتل برمح', 'training_building_gid' => 19, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 1], ['gid' => 19, 'level' => 3]], 'training_cost' => ['wood' => 145, 'clay' => 70, 'iron' => 85, 'crop' => 40], 'crop_upkeep' => 1],
        13 => ['tribe_id' => 2, 'tribe_slot' => 3, 'english' => 'Axeman', 'arabic' => 'مقاتل بفأس', 'training_building_gid' => 19, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 3], ['gid' => 13, 'level' => 1]], 'training_cost' => ['wood' => 130, 'clay' => 120, 'iron' => 170, 'crop' => 70], 'crop_upkeep' => 1],
        14 => ['tribe_id' => 2, 'tribe_slot' => 4, 'english' => 'Scout', 'arabic' => 'الكشّاف', 'training_building_gid' => 19, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 1], ['gid' => 15, 'level' => 5]], 'training_cost' => ['wood' => 160, 'clay' => 100, 'iron' => 50, 'crop' => 50], 'crop_upkeep' => 1],
        15 => ['tribe_id' => 2, 'tribe_slot' => 5, 'english' => 'Paladin', 'arabic' => 'مدافع الجرمان', 'training_building_gid' => 20, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 5], ['gid' => 20, 'level' => 3]], 'training_cost' => ['wood' => 370, 'clay' => 270, 'iron' => 290, 'crop' => 75], 'crop_upkeep' => 2],
        16 => ['tribe_id' => 2, 'tribe_slot' => 6, 'english' => 'Teutonic Knight', 'arabic' => 'فارس الجرمان', 'training_building_gid' => 20, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 15], ['gid' => 20, 'level' => 10]], 'training_cost' => ['wood' => 450, 'clay' => 515, 'iron' => 480, 'crop' => 80], 'crop_upkeep' => 3],
        17 => ['tribe_id' => 2, 'tribe_slot' => 7, 'english' => 'Ram', 'arabic' => 'مدقّ رأس الكبش', 'training_building_gid' => 21, 'training_supported' => false, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 10], ['gid' => 21, 'level' => 1]], 'training_cost' => ['wood' => 1000, 'clay' => 300, 'iron' => 350, 'crop' => 70], 'crop_upkeep' => 3],
        18 => ['tribe_id' => 2, 'tribe_slot' => 8, 'english' => 'Catapult', 'arabic' => 'منجنيق', 'training_building_gid' => 21, 'training_supported' => false, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 21, 'level' => 10], ['gid' => 22, 'level' => 15]], 'training_cost' => ['wood' => 900, 'clay' => 1200, 'iron' => 600, 'crop' => 60], 'crop_upkeep' => 6],
        21 => ['tribe_id' => 3, 'tribe_slot' => 1, 'english' => 'Phalanx', 'arabic' => 'العسكري المقدوني', 'training_building_gid' => 19, 'training_supported' => true, 'initially_unlocked' => true, 'research_requirements' => [], 'training_cost' => ['wood' => 100, 'clay' => 130, 'iron' => 55, 'crop' => 30], 'crop_upkeep' => 1],
        22 => ['tribe_id' => 3, 'tribe_slot' => 2, 'english' => 'Swordsman', 'arabic' => 'المبارز', 'training_building_gid' => 19, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 3], ['gid' => 13, 'level' => 1]], 'training_cost' => ['wood' => 140, 'clay' => 150, 'iron' => 185, 'crop' => 60], 'crop_upkeep' => 1],
        23 => ['tribe_id' => 3, 'tribe_slot' => 3, 'english' => 'Pathfinder', 'arabic' => 'وحدة الاستطلاع', 'training_building_gid' => 20, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 5], ['gid' => 20, 'level' => 1]], 'training_cost' => ['wood' => 170, 'clay' => 150, 'iron' => 20, 'crop' => 40], 'crop_upkeep' => 2],
        24 => ['tribe_id' => 3, 'tribe_slot' => 4, 'english' => 'Theutates Thunder', 'arabic' => 'رعد الإغريق', 'training_building_gid' => 20, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 5], ['gid' => 20, 'level' => 3]], 'training_cost' => ['wood' => 350, 'clay' => 450, 'iron' => 230, 'crop' => 60], 'crop_upkeep' => 2],
        25 => ['tribe_id' => 3, 'tribe_slot' => 5, 'english' => 'Druidrider', 'arabic' => 'الفارس الصلب', 'training_building_gid' => 20, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 5], ['gid' => 20, 'level' => 5]], 'training_cost' => ['wood' => 360, 'clay' => 330, 'iron' => 280, 'crop' => 120], 'crop_upkeep' => 2],
        26 => ['tribe_id' => 3, 'tribe_slot' => 6, 'english' => 'Haeduan', 'arabic' => 'الفارس المقنّع', 'training_building_gid' => 20, 'training_supported' => true, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 15], ['gid' => 20, 'level' => 10]], 'training_cost' => ['wood' => 500, 'clay' => 620, 'iron' => 675, 'crop' => 170], 'crop_upkeep' => 3],
        27 => ['tribe_id' => 3, 'tribe_slot' => 7, 'english' => 'Ram', 'arabic' => 'مدقّ رأس الكبش', 'training_building_gid' => 21, 'training_supported' => false, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 22, 'level' => 10], ['gid' => 21, 'level' => 1]], 'training_cost' => ['wood' => 950, 'clay' => 555, 'iron' => 330, 'crop' => 75], 'crop_upkeep' => 3],
        28 => ['tribe_id' => 3, 'tribe_slot' => 8, 'english' => 'Trebuchet', 'arabic' => 'المقلاع الحربي', 'training_building_gid' => 21, 'training_supported' => false, 'initially_unlocked' => false, 'research_requirements' => [['gid' => 21, 'level' => 10], ['gid' => 22, 'level' => 15]], 'training_cost' => ['wood' => 960, 'clay' => 1450, 'iron' => 630, 'crop' => 90], 'crop_upkeep' => 6],
    ];

    /** @return array<int, array<string, mixed>> */
    public static function definitions(): array
    {
        return self::DEFINITIONS;
    }

    /** @return array<string, mixed>|null */
    public static function definition(int $unitId): ?array
    {
        $definition = self::DEFINITIONS[$unitId] ?? null;

        return $definition === null ? null : self::decorate($unitId, $definition);
    }

    /** @return list<array<string, mixed>> */
    public static function definitionsForTribe(?int $tribeId): array
    {
        $definitions = [];

        foreach (self::DEFINITIONS as $unitId => $definition) {
            if ($definition['tribe_id'] === $tribeId) {
                $definitions[] = self::decorate($unitId, $definition);
            }
        }

        return $definitions;
    }

    public static function unitIdForTribeSlot(?int $tribeId, int $tribeSlot): ?int
    {
        foreach (self::DEFINITIONS as $unitId => $definition) {
            if ($definition['tribe_id'] === $tribeId && $definition['tribe_slot'] === $tribeSlot) {
                return $unitId;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $definition */
    private static function decorate(int $unitId, array $definition): array
    {
        return [
            'unit_id' => $unitId,
            'unit_key' => 'u'.$unitId,
            'tribe_key' => 't'.$definition['tribe_slot'],
            'icon_path' => 'assets/troops-icons/u'.$unitId.'.png',
            ...$definition,
        ];
    }
}
