<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * Reusable search + sort state for list screens. A component sets a default sort
 * field and applies these to its query; the view uses <x-th> for sortable
 * headers and a search box bound to $search.
 */
trait WithTableTools
{
    #[Url(as: 'q')]
    public string $search = '';

    public string $sortField = '';

    public string $sortDir = 'asc';

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }
    }

    /** Apply the current sort to a query, falling back to a default column. */
    protected function applySort($query, string $default)
    {
        return $query->orderBy($this->sortField ?: $default, $this->sortDir);
    }
}
