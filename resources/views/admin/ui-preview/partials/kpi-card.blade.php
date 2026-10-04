<div class="card kpi">
    <div class="kpi-top"><span>{{ $label }}</span><span class="material-symbols-outlined">{{ $icon ?? 'monitoring' }}</span></div>
    <div class="kpi-value">{{ $value }}</div>
    <div class="kpi-foot">{{ $foot ?? '' }}</div>
</div>