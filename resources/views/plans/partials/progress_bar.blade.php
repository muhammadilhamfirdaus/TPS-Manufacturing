@php
    $bgClass = 'bg-success'; 
    if ($pct > 100) $bgClass = 'bg-danger'; 
    elseif ($pct > 90) $bgClass = 'bg-warning';
    
    $width = $pct > 100 ? 100 : $pct;
@endphp

<div class="d-flex align-items-center" style="font-size: 0.7rem;">
    <div class="me-1 fw-bold text-end {{ $pct > 100 ? 'text-danger' : '' }}" style="width: 30px;">
        {{ round($pct) }}%
    </div>
    <div class="progress flex-grow-1" style="height: 6px; background-color: #f1f5f9;">
        <div class="progress-bar {{ $bgClass }} rounded-pill" role="progressbar" style="width: {{ $width }}%;"></div>
    </div>
</div>