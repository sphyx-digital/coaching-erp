<?php

namespace App\Livewire\Concerns;

/**
 * Row multi-selection for list screens. The component sets $pageIds in render()
 * to the ids currently visible, so "select all" and the selected-count helpers
 * work against the current filter/page.
 */
trait WithBulkSelect
{
    /** @var array<int,string> selected row ids (strings, as checkboxes bind) */
    public array $selected = [];

    /** @var array<int,int> ids currently visible; set in render() */
    public array $pageIds = [];

    public function selectAllVisible(): void
    {
        $this->selected = array_map('strval', $this->pageIds);
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function selectedCount(): int
    {
        return count($this->selected);
    }

    /** @return array<int,int> selected ids as integers */
    protected function selectedIds(): array
    {
        return array_map('intval', $this->selected);
    }
}
