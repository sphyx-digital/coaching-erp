@props(['head' => []])

{{-- Wide content scrolls inside its own container; the page never scrolls sideways. --}}
<div class="table-wrap">
    <table {{ $attributes->merge(['class' => 'table']) }}>
        @if (count($head))
            <thead>
                <tr>
                    @foreach ($head as $col)
                        <th @class(['num' => is_array($col) && ($col['num'] ?? false)])>{{ is_array($col) ? $col['label'] : $col }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
