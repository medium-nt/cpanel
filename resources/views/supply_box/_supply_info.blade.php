@props(['supply'])

<div class="text-muted mb-2" style="font-size: 0.85rem;">
    <b>#{{ $supply->supply_id }}</b>
    @if($supply->marketplace_id === 1 && ($supply->draft_params['order_number'] ?? null))
        {{ $supply->draft_params['order_number'] }}
    @endif
    @if($supply->cluster)
        ({{ $supply->cluster }})
    @endif
</div>
