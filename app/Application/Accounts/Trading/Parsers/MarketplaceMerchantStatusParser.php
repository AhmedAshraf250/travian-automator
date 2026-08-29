<?php

namespace App\Application\Accounts\Trading\Parsers;

class MarketplaceMerchantStatusParser
{
    /**
     * @return array{available: int, total: int}|null
     */
    public function parse(string $html): ?array
    {
        if (preg_match('/<div[^>]*class=["\'][^"\']*whereAreMyMerchants[^"\']*["\'][^>]*>(.*?)<\/div>/isu', $html, $matches) !== 1) {
            return null;
        }

        $text = html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match_all('/\d+/u', $this->normalizeUnicodeDigits($text), $numberMatches) === false || count($numberMatches[0]) < 2) {
            return null;
        }

        return [
            'available' => max(0, (int) $numberMatches[0][0]),
            'total' => max(0, (int) $numberMatches[0][1]),
        ];
    }

    protected function normalizeUnicodeDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }
}
