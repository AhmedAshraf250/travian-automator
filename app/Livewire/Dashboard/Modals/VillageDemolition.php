<?php

namespace App\Livewire\Dashboard\Modals;

use App\Livewire\Dashboard\Concerns\ManagesVillageDemolition;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class VillageDemolition extends Component
{
    use ManagesVillageDemolition {
        openVillageDemolitionModal as protected loadVillageDemolition;
    }

    #[On('dashboard-open-village-demolition')]
    public function openVillageDemolitionModal(int $villageId): void
    {
        $this->loadVillageDemolition($villageId);
    }

    public function render(): View
    {
        return view('livewire.dashboard.modals.village-demolition', [
            'demolitionSnapshot' => $this->showVillageDemolitionModal ? $this->demolitionSnapshot() : [],
            'demolitionBuildings' => $this->showVillageDemolitionModal ? $this->demolitionBuildings() : collect(),
        ]);
    }
}
