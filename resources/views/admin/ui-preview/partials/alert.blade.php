@php $tone = $tone ?? 'success'; @endphp
<div class="alert alert-{{ $tone }}">
    <span class="material-symbols-outlined">{{ $icon ?? ($tone === 'warning' ? 'warning' : 'check_circle') }}</span>
    <div><strong>{{ $title ?? 'Status' }}</strong><br><span>{{ $message ?? '' }}</span></div>
</div>