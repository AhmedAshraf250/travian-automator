<?php

namespace App\Application\Travian;

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
        10 => ['english' => 'Warehouse', 'arabic' => 'المخزن', 'kind' => 'building', 'field_key' => null, 'build_category' => 1, 'aliases' => []],
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
        41 => ['english' => 'Horse Drinking Trough', 'arabic' => 'بِئْر سقي الخيول', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => ['بئر سقي الخيول']],
        46 => ['english' => 'Hospital', 'arabic' => 'مستشفى', 'kind' => 'building', 'field_key' => null, 'build_category' => 2, 'aliases' => []],
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
