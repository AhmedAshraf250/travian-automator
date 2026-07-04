<?php

use App\Application\Accounts\Construction\Parsers\MainBuildingDemolitionParser;

test('main building demolition parser extracts available buildings and active cancel link', function () {
    $html = <<<'HTML'
    <select id="demolish" name="abriss" class="dropdown">
        <option value="19">19 مخزن 10</option>
        <option value="21">21 المخبأ 7</option>
        <option value="26">26 المبنى الرئيسي 15</option>
    </select>
    <table cellpadding="1" cellspacing="1" id="demolish" class="transparent">
        <tr>
            <td class="abort">
                <button type="button" class="icon" title="إلغاء" onclick="this.disabled = true; window.location.href = '/build.php?gid=15&amp;del=932338'; return false;"></button>
            </td>
            <td>المخبأ <span class="level">المستوى 6</span></td>
            <td class="times"><span class="timer" counting="down" value="512">0:08:32</span> ساعات</td>
            <td class="times">ينتهي الساعة 20:30</td>
        </tr>
    </table>
    HTML;

    $snapshot = app(MainBuildingDemolitionParser::class)->parse($html);

    expect($snapshot['main_building_level'])->toBe(15)
        ->and($snapshot['available_buildings'])->toContain([
            'slot_id' => 21,
            'name' => 'المخبأ',
            'level' => 7,
        ])
        ->and($snapshot['active']['name'])->toBe('المخبأ')
        ->and($snapshot['active']['target_level'])->toBe(6)
        ->and($snapshot['active']['remaining_seconds'])->toBe(512)
        ->and($snapshot['active']['cancel_uri'])->toBe('/build.php?gid=15&del=932338');
});
