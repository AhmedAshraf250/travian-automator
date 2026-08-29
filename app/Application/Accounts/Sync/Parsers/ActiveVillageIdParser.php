<?php

namespace App\Application\Accounts\Sync\Parsers;

class ActiveVillageIdParser
{
    public function parse(string $html): ?string
    {
        if (preg_match('/id=["\']villageName["\'][\s\S]*?data-did=(["\']?)([^"\'\s>]+)\1/u', $html, $matches) !== 1) {
            return null;
        }

        return html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
