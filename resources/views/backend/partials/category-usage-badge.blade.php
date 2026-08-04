{{--
  Usage count badge for backend category tree rows.
  Expected: $usageCount (int), optional $usageLabel, $usageTitle
--}}
@php
    $usageCount = (int) ($usageCount ?? 0);
    $usageLabel = $usageLabel ?? 'Uses';
    $usageTitle = $usageTitle ?? ($usageLabel.': '.$usageCount);
@endphp
<span class="qcat-usage-badge" title="{{ $usageTitle }}">
    {{ $usageLabel }}: {{ number_format($usageCount) }}
</span>
