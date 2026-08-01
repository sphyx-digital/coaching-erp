@props(['title'])

<div class="page-header">
    <h1 class="page-header__title">{{ $title }}</h1>
    @isset($actions)
        <div style="display:flex; gap: var(--space-2); flex-wrap: wrap;">{{ $actions }}</div>
    @endisset
</div>
