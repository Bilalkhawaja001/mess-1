@php $tone = $tone ?? 'info'; @endphp
<span class="chip chip-{{ $tone }}">{{ $slot ?? $label ?? '' }}</span>