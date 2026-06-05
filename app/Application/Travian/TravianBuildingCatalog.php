<?php

namespace App\Application\Travian;

use App\Application\Travian\Data\BuildingEligibility;
use App\Models\Account;
use App\Models\Village;

/**
 * Central lookup catalog for Travian building and field gids.
 */
final class TravianBuildingCatalog
{
    /**
     * Canonical building metadata indexed by gid.
     *
     * @var array<int, array{
     *     english: string,
     *     arabic: string,
     *     kind: 'field'|'building',
     *     field_key: 'wood'|'clay'|'iron'|'crop'|null,
     *     build_category: 1|2|3|null,
     *     aliases: list<string>
     * }>
     */
    private const DEFINITIONS = [
        1 => ['english' => 'Woodcutter', 'arabic' => 'الحطاب', 'kind' => 'field', 'field_key' => 'wood', 'build_category' => null, 'aliases' => ['حقل الخشب']],
        2 => ['english' => 'Clay Pit', 'arabic' => 'حفرة الطين', 'kind' => 'field', 'field_key' => 'clay', 'build_category' => null, 'aliases' => []],
        3 => ['english' => 'Iron Mine', 'arabic' => 'منجم حديد', 'kind' => 'field', 'field_key' => 'iron', 'build_category' => null, 'aliases' => ['منجم الحديد']],
        4 => ['english' => 'Cropland', 'arabic' => 'حقل القمح', 'kind' => 'field', 'field_key' => 'crop', 'build_category' => null, 'aliases' => []],
        5 => ['english' => 'Sawmill', 'arabic' => 'معمل النجارة', 'kind' => 'building', 'field_key' => null, 'build_category' => 3, 'aliases' => ['معمل نشر الخشب']],
        6 => ['english' => 'Brickyard', 'arabic' => 'مصنع البلوك', 'kind' => 'building', 'field_key' => null, 'build_category' => 3, 'aliases' => []],
        7 => ['english' => 'Iron Foundry', 'arabic' => 'مصنع الحديد', 'kind' => 'building', 'field_key' => null, 'build_category' => 3, 'aliases' => []],
        8 => ['english' => 'Grain Mill', 'arabic' => 'المطاحن', 'kind' => 'building', 'field_key' => null, 'build_category' => 3, 'aliases' => ['مطحنة الحبوب']],
        9 => ['english' => 'Bakery', 'arabic' => 'المخابز', 'kind' => 'building', 'field_key' => null, 'build_category' => 3, 'aliases' => ['المخبز']],
        10 => ['english' => 'Warehouse', 'arabic' => 'المخزن', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => ['مخزن']],
        11 => ['english' => 'Granary', 'arabic' => 'مخزن الحبوب', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => ['صومعة']],
        13 => ['english' => 'Smithy', 'arabic' => 'أفران صهر الحديد', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => ['الحدادة']],
        14 => ['english' => 'Tournament Square', 'arabic' => 'ساحة البطولة', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => []],
        15 => ['english' => 'Main Building', 'arabic' => 'المبنى الرئيسي', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => []],
        16 => ['english' => 'Rally Point', 'arabic' => 'نقطة التجمع', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => []],
        17 => ['english' => 'Marketplace', 'arabic' => 'السوق', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => []],
        18 => ['english' => 'Embassy', 'arabic' => 'السفارة', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => []],
        19 => ['english' => 'Barracks', 'arabic' => 'الثكنة', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => []],
        20 => ['english' => 'Stable', 'arabic' => 'الإسطبل', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => []],
        21 => ['english' => 'Workshop', 'arabic' => 'المصانع الحربية', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => []],
        22 => ['english' => 'Academy', 'arabic' => 'الأكاديمية', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => []],
        23 => ['english' => 'Cranny', 'arabic' => 'المخبأ', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => []],
        24 => ['english' => 'Town Hall', 'arabic' => 'البلدية', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => []],
        25 => ['english' => 'Residence', 'arabic' => 'السكن', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => []],
        26 => ['english' => 'Palace', 'arabic' => 'القصر', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => []],
        27 => ['english' => 'Treasury', 'arabic' => 'الخزنة', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => []],
        28 => ['english' => 'Trade Office', 'arabic' => 'المكتب التجاري', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => []],
        31 => ['english' => 'City Wall', 'arabic' => 'حائط المدينة', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => ['سور المدينة']],
        32 => ['english' => 'Earth Wall', 'arabic' => 'الحائط الأرضي', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => []],
        33 => ['english' => 'Palisade', 'arabic' => 'الحاجز', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => []],
        34 => ['english' => 'Stonemason\'s Lodge', 'arabic' => 'الحجار', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => ['الحجارون']],
        35 => ['english' => 'Brewery', 'arabic' => 'المقهى', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => []],
        36 => ['english' => 'Trapper', 'arabic' => 'الصياد', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => []],
        37 => ['english' => 'Hero\'s Mansion', 'arabic' => 'قصر الأبطال', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => []],
        38 => ['english' => 'Great Warehouse', 'arabic' => 'المخزن الكبير', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => []],
        39 => ['english' => 'Great Granary', 'arabic' => 'مخزن الحبوب الكبير', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => ['الصومعة الكبرى']],
        41 => ['english' => 'Horse Drinking Trough', 'arabic' => 'بِئْر سقي الخيول', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => ['بئر سقي الخيول', 'ساقية الخيول']],
        46 => ['english' => 'Hospital', 'arabic' => 'مستشفى', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => []],
    ];

    /**
     * Executable construction rules for level 1 building availability.
     *
     * @var array<int, array{
     *     cost: array{wood: int, clay: int, iron: int, crop: int, crop_consumption: int, total_resources: int},
     *     requirements: list<array{gid: int, level: int}>,
     *     max_level?: int,
     *     capital_only?: bool,
     *     account_unique?: bool,
     *     mutually_exclusive?: list<int>
     * }>
     */
    private const CONSTRUCTION_RULES = [
        5 => ['cost' => ['wood' => 520, 'clay' => 380, 'iron' => 290, 'crop' => 90, 'total_resources' => 1280, 'crop_consumption' => 4], 'requirements' => [['gid' => 1, 'level' => 10], ['gid' => 15, 'level' => 5]], 'max_level' => 5],
        6 => ['cost' => ['wood' => 440, 'clay' => 480, 'iron' => 320, 'crop' => 50, 'total_resources' => 1290, 'crop_consumption' => 3], 'requirements' => [['gid' => 2, 'level' => 10], ['gid' => 15, 'level' => 5]], 'max_level' => 5],
        7 => ['cost' => ['wood' => 200, 'clay' => 450, 'iron' => 510, 'crop' => 120, 'total_resources' => 1280, 'crop_consumption' => 6], 'requirements' => [['gid' => 3, 'level' => 10], ['gid' => 15, 'level' => 5]], 'max_level' => 5],
        8 => ['cost' => ['wood' => 500, 'clay' => 440, 'iron' => 380, 'crop' => 1240, 'total_resources' => 2560, 'crop_consumption' => 3], 'requirements' => [['gid' => 4, 'level' => 5]], 'max_level' => 5],
        9 => ['cost' => ['wood' => 1200, 'clay' => 1480, 'iron' => 870, 'crop' => 1600, 'total_resources' => 5150, 'crop_consumption' => 4], 'requirements' => [['gid' => 4, 'level' => 10], ['gid' => 15, 'level' => 5], ['gid' => 8, 'level' => 5]], 'max_level' => 5],
        10 => ['cost' => ['wood' => 130, 'clay' => 160, 'iron' => 90, 'crop' => 40, 'total_resources' => 420, 'crop_consumption' => 1], 'requirements' => [['gid' => 15, 'level' => 1]]],
        11 => ['cost' => ['wood' => 80, 'clay' => 100, 'iron' => 70, 'crop' => 20, 'total_resources' => 270, 'crop_consumption' => 1], 'requirements' => [['gid' => 15, 'level' => 1]]],
        13 => ['cost' => ['wood' => 180, 'clay' => 250, 'iron' => 500, 'crop' => 160, 'total_resources' => 1090, 'crop_consumption' => 4], 'requirements' => [['gid' => 15, 'level' => 3], ['gid' => 22, 'level' => 1]]],
        14 => ['cost' => ['wood' => 1750, 'clay' => 2250, 'iron' => 1530, 'crop' => 240, 'total_resources' => 5770, 'crop_consumption' => 1], 'requirements' => [['gid' => 16, 'level' => 15]]],
        15 => ['cost' => ['wood' => 70, 'clay' => 40, 'iron' => 60, 'crop' => 20, 'total_resources' => 190, 'crop_consumption' => 2], 'requirements' => []],
        16 => ['cost' => ['wood' => 110, 'clay' => 160, 'iron' => 90, 'crop' => 70, 'total_resources' => 430, 'crop_consumption' => 1], 'requirements' => []],
        17 => ['cost' => ['wood' => 80, 'clay' => 70, 'iron' => 120, 'crop' => 70, 'total_resources' => 340, 'crop_consumption' => 4], 'requirements' => [['gid' => 15, 'level' => 3], ['gid' => 10, 'level' => 1], ['gid' => 11, 'level' => 1]]],
        18 => ['cost' => ['wood' => 180, 'clay' => 130, 'iron' => 150, 'crop' => 80, 'total_resources' => 540, 'crop_consumption' => 3], 'requirements' => [['gid' => 15, 'level' => 1]]],
        19 => ['cost' => ['wood' => 210, 'clay' => 140, 'iron' => 260, 'crop' => 120, 'total_resources' => 730, 'crop_consumption' => 4], 'requirements' => [['gid' => 16, 'level' => 1], ['gid' => 15, 'level' => 3]]],
        20 => ['cost' => ['wood' => 260, 'clay' => 140, 'iron' => 220, 'crop' => 100, 'total_resources' => 720, 'crop_consumption' => 5], 'requirements' => [['gid' => 13, 'level' => 3], ['gid' => 22, 'level' => 5]]],
        21 => ['cost' => ['wood' => 460, 'clay' => 510, 'iron' => 600, 'crop' => 320, 'total_resources' => 1890, 'crop_consumption' => 3], 'requirements' => [['gid' => 22, 'level' => 10], ['gid' => 15, 'level' => 5]]],
        22 => ['cost' => ['wood' => 220, 'clay' => 160, 'iron' => 90, 'crop' => 40, 'total_resources' => 510, 'crop_consumption' => 4], 'requirements' => [['gid' => 19, 'level' => 3], ['gid' => 15, 'level' => 3]]],
        23 => ['cost' => ['wood' => 40, 'clay' => 50, 'iron' => 30, 'crop' => 10, 'total_resources' => 130, 'crop_consumption' => 0], 'requirements' => []],
        24 => ['cost' => ['wood' => 1250, 'clay' => 1110, 'iron' => 1260, 'crop' => 600, 'total_resources' => 4220, 'crop_consumption' => 4], 'requirements' => [['gid' => 15, 'level' => 10], ['gid' => 22, 'level' => 10]]],
        25 => ['cost' => ['wood' => 580, 'clay' => 460, 'iron' => 350, 'crop' => 180, 'total_resources' => 1570, 'crop_consumption' => 1], 'requirements' => [['gid' => 15, 'level' => 5]], 'mutually_exclusive' => [26]],
        26 => ['cost' => ['wood' => 550, 'clay' => 800, 'iron' => 750, 'crop' => 250, 'total_resources' => 2350, 'crop_consumption' => 1], 'requirements' => [['gid' => 18, 'level' => 1], ['gid' => 15, 'level' => 5]], 'capital_only' => true, 'account_unique' => true, 'mutually_exclusive' => [25]],
        27 => ['cost' => ['wood' => 2880, 'clay' => 2740, 'iron' => 2580, 'crop' => 990, 'total_resources' => 9190, 'crop_consumption' => 4], 'requirements' => [['gid' => 15, 'level' => 10]]],
        28 => ['cost' => ['wood' => 1400, 'clay' => 1330, 'iron' => 1200, 'crop' => 400, 'total_resources' => 4330, 'crop_consumption' => 3], 'requirements' => [['gid' => 17, 'level' => 20], ['gid' => 20, 'level' => 10]]],
        31 => ['cost' => ['wood' => 70, 'clay' => 90, 'iron' => 170, 'crop' => 70, 'total_resources' => 400, 'crop_consumption' => 0], 'requirements' => []],
        32 => ['cost' => ['wood' => 120, 'clay' => 200, 'iron' => 0, 'crop' => 80, 'total_resources' => 400, 'crop_consumption' => 0], 'requirements' => []],
        33 => ['cost' => ['wood' => 160, 'clay' => 100, 'iron' => 80, 'crop' => 60, 'total_resources' => 400, 'crop_consumption' => 0], 'requirements' => []],
        34 => ['cost' => ['wood' => 155, 'clay' => 130, 'iron' => 125, 'crop' => 70, 'total_resources' => 480, 'crop_consumption' => 2], 'requirements' => [['gid' => 15, 'level' => 5]], 'capital_only' => true],
        35 => ['cost' => ['wood' => 3210, 'clay' => 2050, 'iron' => 2750, 'crop' => 3830, 'total_resources' => 11840, 'crop_consumption' => 6], 'requirements' => [['gid' => 11, 'level' => 20], ['gid' => 16, 'level' => 10]], 'capital_only' => true],
        36 => ['cost' => ['wood' => 80, 'clay' => 120, 'iron' => 70, 'crop' => 90, 'total_resources' => 360, 'crop_consumption' => 4], 'requirements' => [['gid' => 16, 'level' => 1]]],
        37 => ['cost' => ['wood' => 700, 'clay' => 670, 'iron' => 700, 'crop' => 240, 'total_resources' => 2310, 'crop_consumption' => 2], 'requirements' => [['gid' => 15, 'level' => 3], ['gid' => 16, 'level' => 1]]],
        38 => ['cost' => ['wood' => 650, 'clay' => 800, 'iron' => 450, 'crop' => 200, 'total_resources' => 2100, 'crop_consumption' => 1], 'requirements' => [['gid' => 15, 'level' => 10], ['gid' => 40, 'level' => 0]]],
        39 => ['cost' => ['wood' => 400, 'clay' => 500, 'iron' => 350, 'crop' => 100, 'total_resources' => 1350, 'crop_consumption' => 1], 'requirements' => [['gid' => 15, 'level' => 10], ['gid' => 40, 'level' => 0]]],
        41 => ['cost' => ['wood' => 780, 'clay' => 420, 'iron' => 660, 'crop' => 540, 'total_resources' => 2400, 'crop_consumption' => 5], 'requirements' => [['gid' => 20, 'level' => 20], ['gid' => 16, 'level' => 10]]],
        46 => ['cost' => ['wood' => 320, 'clay' => 280, 'iron' => 420, 'crop' => 360, 'total_resources' => 1380, 'crop_consumption' => 3], 'requirements' => [['gid' => 15, 'level' => 10], ['gid' => 22, 'level' => 15]]],
    ];

    /**
     * Return the full gid catalog.
     *
     * @return array<int, array{
     *     english: string,
     *     arabic: string,
     *     kind: 'field'|'building',
     *     field_key: 'wood'|'clay'|'iron'|'crop'|null,
     *     build_category: 1|2|3|null,
     *     aliases: list<string>
     * }>
     */
    public static function definitions(): array
    {
        return self::DEFINITIONS;
    }

    /**
     * Return the construction rule for a building gid.
     *
     * @return array{
     *     cost: array{wood: int, clay: int, iron: int, crop: int, crop_consumption: int, total_resources: int},
     *     requirements: list<array{gid: int, level: int}>,
     *     max_level?: int,
     *     capital_only?: bool,
     *     account_unique?: bool,
     *     mutually_exclusive?: list<int>
     * }|null
     */
    public static function constructionRuleForGid(int $gid): ?array
    {
        return self::CONSTRUCTION_RULES[$gid] ?? null;
    }

    /**
     * Return construction prerequisites for a building gid.
     *
     * @return list<array{gid: int, level: int}>
     */
    public static function requirementsForGid(int $gid): array
    {
        return self::constructionRuleForGid($gid)['requirements'] ?? [];
    }

    /**
     * Return the maximum level known for one gid.
     */
    public static function maxLevelForGid(int $gid): ?int
    {
        return self::constructionRuleForGid($gid)['max_level'] ?? null;
    }

    /**
     * Return the level-one construction resources for one gid.
     *
     * @return array{wood: int, clay: int, iron: int, crop: int, crop_consumption: int, total_resources: int}|null
     */
    public static function levelOneCostForGid(int $gid): ?array
    {
        return self::constructionRuleForGid($gid)['cost'] ?? null;
    }

    /**
     * Determine whether a building can be constructed from local known rules.
     */
    public static function canConstructInVillage(int $gid, Account $account, Village $village): BuildingEligibility
    {
        $rule = self::constructionRuleForGid($gid);

        if ($rule === null) {
            return new BuildingEligibility(false, 'unknown_building_rule', [], null);
        }

        $tribeId = $village->runtimeState?->tribe_id !== null ? (int) $village->runtimeState->tribe_id : null;

        if (! self::supportsTribe($gid, $tribeId)) {
            return new BuildingEligibility(false, 'tribe_restricted', [], $rule['cost']);
        }

        if (($rule['capital_only'] ?? false) && ! (bool) $village->is_capital) {
            return new BuildingEligibility(false, 'capital_required', [], $rule['cost']);
        }

        foreach ($rule['mutually_exclusive'] ?? [] as $exclusiveGid) {
            if (self::highestKnownLevel($village, $exclusiveGid) > 0) {
                return new BuildingEligibility(false, 'mutually_exclusive_building_exists', [], $rule['cost']);
            }
        }

        if (($rule['account_unique'] ?? false) && self::accountHasBuildingGid($account, $gid, $village)) {
            return new BuildingEligibility(false, 'account_unique_building_exists', [], $rule['cost']);
        }

        $missingRequirements = [];

        foreach ($rule['requirements'] as $requirement) {
            $currentLevel = self::highestKnownLevel($village, (int) $requirement['gid']);

            if ($currentLevel < (int) $requirement['level']) {
                $missingRequirements[] = [
                    'gid' => (int) $requirement['gid'],
                    'name' => self::nameForGid((int) $requirement['gid']),
                    'required_level' => (int) $requirement['level'],
                    'current_level' => $currentLevel,
                ];
            }
        }

        return new BuildingEligibility(
            allowed: $missingRequirements === [],
            blockedReason: $missingRequirements === [] ? null : 'missing_requirements',
            missingRequirements: $missingRequirements,
            requiredResources: $rule['cost'],
        );
    }

    /**
     * Resolve the metadata for one gid.
     *
     * @return array{
     *     english: string,
     *     arabic: string,
     *     kind: 'field'|'building',
     *     field_key: 'wood'|'clay'|'iron'|'crop'|null,
     *     build_category: 1|2|3|null,
     *     aliases: list<string>
     * }|null
     */
    public static function definition(int $gid): ?array
    {
        return self::DEFINITIONS[$gid] ?? null;
    }

    /**
     * Resolve the preferred localized name for one gid.
     */
    public static function nameForGid(int $gid, string $locale = 'ar'): ?string
    {
        $definition = self::definition($gid);

        if ($definition === null) {
            return null;
        }

        return $locale === 'en'
            ? $definition['english']
            : $definition['arabic'];
    }

    /**
     * Resolve the gid that best matches a localized building name.
     */
    public static function gidForName(?string $name): ?int
    {
        $normalizedName = self::normalizeName($name);

        if ($normalizedName === '') {
            return null;
        }

        foreach (self::DEFINITIONS as $gid => $definition) {
            foreach (self::candidateNames($definition) as $candidateName) {
                if (self::normalizeName($candidateName) === $normalizedName) {
                    return $gid;
                }
            }
        }

        return null;
    }

    /**
     * Determine whether the gid belongs to a resource field.
     */
    public static function isFieldGid(int $gid): bool
    {
        return (self::definition($gid)['kind'] ?? null) === 'field';
    }

    /**
     * Resolve the logical resource key for a resource field gid.
     */
    public static function fieldKeyForGid(int $gid): ?string
    {
        return self::definition($gid)['field_key'] ?? null;
    }

    /**
     * Resolve the empty-slot build category for the given gid.
     */
    public static function buildCategoryForGid(int $gid): ?int
    {
        return self::definition($gid)['build_category'] ?? null;
    }

    /**
     * Infer whether a queued item consumes the field queue or village-building queue.
     */
    public static function queueKindForName(?string $name): ?string
    {
        $gid = self::gidForName($name);

        if ($gid === null) {
            return null;
        }

        return self::queueKindForGid($gid);
    }

    /**
     * Infer the queue kind from a known gid.
     */
    public static function queueKindForGid(int $gid): string
    {
        return self::isFieldGid($gid) ? 'field' : 'building';
    }

    /**
     * Determine whether the tribe has separate field and building queues.
     */
    public static function isRomanTribe(?int $tribeId): bool
    {
        return $tribeId === 1;
    }

    /**
     * Determine whether the gid is available for the provided tribe.
     */
    public static function supportsTribe(int $gid, ?int $tribeId): bool
    {
        return match ($gid) {
            31 => $tribeId === 1,
            32 => $tribeId === 2,
            33 => $tribeId === 3,
            35 => $tribeId === 2,
            36 => $tribeId === 3,
            41 => $tribeId === 1,
            default => true,
        };
    }

    /**
     * Resolve the fixed gid required for a special village-center slot.
     */
    public static function fixedSlotGidForSlot(int $slotId, ?int $tribeId): ?int
    {
        return match ($slotId) {
            26 => 15,
            39 => 16,
            40 => match ($tribeId) {
                1 => 31,
                2 => 32,
                3 => 33,
                default => null,
            },
            default => null,
        };
    }

    /**
     * Return the preferred fixed slot for a special building gid.
     */
    public static function fixedSlotForGid(int $gid, ?int $tribeId): ?int
    {
        if ($gid === 15) {
            return 26;
        }

        if ($gid === 16) {
            return 39;
        }

        if ($gid === self::fixedSlotGidForSlot(40, $tribeId)) {
            return 40;
        }

        return null;
    }

    /**
     * Return the selectable building options for one tribe.
     *
     * @return list<array{gid: int, label: string, category: int|null}>
     */
    public static function buildingOptionsForTribe(?int $tribeId, string $locale = 'ar'): array
    {
        $options = [];

        foreach (self::DEFINITIONS as $gid => $definition) {
            if ($definition['kind'] !== 'building') {
                continue;
            }

            if (! self::supportsTribe($gid, $tribeId)) {
                continue;
            }

            $options[] = [
                'gid' => $gid,
                'label' => $locale === 'en' ? $definition['english'] : $definition['arabic'],
                'category' => $definition['build_category'],
            ];
        }

        usort($options, static function (array $left, array $right): int {
            $leftCategory = $left['category'] ?? 99;
            $rightCategory = $right['category'] ?? 99;

            if ($leftCategory !== $rightCategory) {
                return $leftCategory <=> $rightCategory;
            }

            return $left['gid'] <=> $right['gid'];
        });

        return $options;
    }

    /**
     * Normalize a localized building name for catalog matching.
     */
    private static function normalizeName(?string $name): string
    {
        if (! is_string($name)) {
            return '';
        }

        $normalizedName = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalizedName = mb_strtolower($normalizedName);
        $normalizedName = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $normalizedName) ?? '';

        return trim($normalizedName);
    }

    /**
     * Return the highest known level for a gid in one village.
     */
    private static function highestKnownLevel(Village $village, int $gid): int
    {
        return (int) $village->buildings
            ->where('building_gid', $gid)
            ->max('current_level');
    }

    /**
     * Determine whether another village under the account already has a gid.
     */
    private static function accountHasBuildingGid(Account $account, int $gid, Village $currentVillage): bool
    {
        return $account->villages()
            ->whereKeyNot($currentVillage->id)
            ->whereHas('buildings', static function ($query) use ($gid): void {
                $query->where('building_gid', $gid)->where('current_level', '>', 0);
            })
            ->exists();
    }

    /**
     * Build the candidate names used for reverse gid lookups.
     *
     * @param  array{
     *     english: string,
     *     arabic: string,
     *     aliases: list<string>
     * }  $definition
     * @return list<string>
     */
    private static function candidateNames(array $definition): array
    {
        return array_values(array_filter([
            $definition['english'],
            $definition['arabic'],
            ...$definition['aliases'],
        ]));
    }
}
