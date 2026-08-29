<?php

namespace App\Livewire\Dashboard\Modals;

use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Livewire\Dashboard\Concerns\ManagesMarketplaceTransfers;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class MarketplaceTransfer extends Component
{
    use ManagesMarketplaceTransfers {
        openMarketplaceTransferModal as protected loadMarketplaceTransfer;
    }

    public string $dashboardRevision = '';

    #[On('dashboard-open-marketplace-transfer')]
    public function openMarketplaceTransferModal(int $villageId): void
    {
        $this->loadMarketplaceTransfer($villageId);
    }

    public function render(): View
    {
        return view('livewire.dashboard.modals.marketplace-transfer', [
            'marketplaceTransferVillages' => $this->showMarketplaceTransferModal ? $this->marketplaceTransferVillages() : collect(),
            'marketplaceTransferCapacity' => $this->showMarketplaceTransferModal ? $this->marketplaceTransferCapacity() : [],
        ]);
    }

    protected function logManualActivity(Account $account, ?Village $village, string $message): void
    {
        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village?->id,
            'activity_type' => ActivityType::Manual,
            'status' => ActivityLogStatus::Done,
            'message' => $message,
            'executed_at' => now(),
        ]);
    }
}
