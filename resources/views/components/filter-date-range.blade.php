@props([
    'id',
    'label',
    'fromName',
    'toName',
    'fromValue' => null,
    'toValue' => null,
])

<div class="filter-group filter-date-range" data-filter-date-range>
    <label for="{{ $id }}-preset" class="filter-label">{{ $label }}</label>

    <select
        id="{{ $id }}-preset"
        class="panel-input w-full text-sm filter-date-preset"
        data-date-preset-select
        data-no-search
        aria-label="{{ $label }} options"
    >
        <option value="">Any time</option>
        <option value="today">Today</option>
        <option value="yesterday">Yesterday</option>
        <option value="last_7_days">Last 7 Days</option>
        <option value="last_30_days">Last 30 Days</option>
        <option value="this_month">This Month</option>
        <option value="last_month">Last Month</option>
        <option value="this_year">This Year</option>
        <option value="custom" @selected(filled($fromValue) || filled($toValue))>Custom Range</option>
    </select>

    <div class="filter-custom-range" data-custom-range @if(!filled($fromValue) && !filled($toValue)) hidden @endif>
        <div class="filter-date-grid">
            <div class="filter-date-field">
                <label for="{{ $id }}-from" class="filter-custom-range__label">From</label>
                <x-date-time-picker
                    name="{{ $fromName }}"
                    id="{{ $id }}-from"
                    mode="date"
                    :value="$fromValue"
                    placeholder="Start date…"
                    input-class="panel-input text-sm"
                    data-range-from="1"
                />
            </div>
            <div class="filter-date-field">
                <label for="{{ $id }}-to" class="filter-custom-range__label">To</label>
                <x-date-time-picker
                    name="{{ $toName }}"
                    id="{{ $id }}-to"
                    mode="date"
                    :value="$toValue"
                    placeholder="End date…"
                    input-class="panel-input text-sm"
                    data-range-to="1"
                />
            </div>
        </div>
        <p class="filter-date-error" data-range-error hidden role="alert">
            The To date must be greater than or equal to the From date.
        </p>
    </div>
</div>
