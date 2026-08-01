@props(['field' => null, 'sort' => '', 'dir' => 'asc', 'num' => false])

<th @class(['num' => $num, 'th-sort' => $field])
    @if ($field) wire:click="sortBy('{{ $field }}')" role="button" tabindex="0"
        wire:keydown.enter="sortBy('{{ $field }}')" aria-sort="{{ $sort === $field ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}" @endif>
    {{ $slot }}
    @if ($field && $sort === $field)
        <span class="th-arrow" aria-hidden="true">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
    @endif
</th>
