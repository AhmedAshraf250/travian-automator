<?php

use App\Application\Accounts\Sync\Parsers\ActiveVillageIdParser;
use App\Application\Accounts\Trading\Parsers\MarketplaceMerchantStatusParser;

test('active village parser supports quoted and unquoted Travian village ids', function (string $attribute, string $expected) {
    expect(app(ActiveVillageIdParser::class)->parse('<input id="villageName" data-did='.$attribute.'>'))->toBe($expected);
})->with([
    'quoted' => ['"23378"', '23378'],
    'unquoted' => ['23379', '23379'],
]);

test('marketplace merchant parser rejects ordinary building pages', function () {
    $parser = app(MarketplaceMerchantStatusParser::class);

    expect($parser->parse('<html><body><h1>Barracks</h1></body></html>'))->toBeNull()
        ->and($parser->parse('<div class="whereAreMyMerchants">التجار: ١٠\\٢٠</div>'))->toBe([
            'available' => 10,
            'total' => 20,
        ]);
});
