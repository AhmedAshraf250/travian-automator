<?php

namespace App\Livewire\Dashboard\Concerns;

/**
 * Shared editable trade state used by both village settings and the TR modal.
 */
trait HasVillageTradeDrafts
{
    public bool $villageSendResourcesDraft = true;

    public bool $villageSupplyResourcesDraft = true;

    public bool $villageSupplyNegativeCropDraft = true;

    public int $villageTradeMaxDurationMinutesDraft = 300;

    public int $villageSendMinResourcePercentageDraft = 30;

    public int $villageSendReserveResourcePercentageDraft = 10;
}
