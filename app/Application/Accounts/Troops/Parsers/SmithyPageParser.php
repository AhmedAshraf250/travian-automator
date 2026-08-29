<?php

namespace App\Application\Accounts\Troops\Parsers;

use App\Application\Accounts\Troops\Data\ParsedResearchPage;

class SmithyPageParser extends ResearchBuildingPageParser
{
    public function parse(string $html): ParsedResearchPage
    {
        return $this->parseResearchPage($html, true);
    }
}
