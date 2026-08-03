<?php

namespace App\Livewire\Search;

use App\Services\Search\SearchService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CommandPalette extends Component
{
    public string $q = '';

    public function updatedQ(): void
    {
        // no-op: results are recomputed on render
    }

    public function clear(): void
    {
        $this->q = '';
    }

    public function render()
    {
        $groups = ($this->q !== '' && ! Auth::user()?->isPortalUser())
            ? app(SearchService::class)->search(Auth::user(), $this->q)
            : [];

        return view('livewire.search.command-palette', ['groups' => $groups]);
    }
}
