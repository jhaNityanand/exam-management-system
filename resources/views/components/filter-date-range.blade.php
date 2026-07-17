@props([
    'id',
    'label',
    'fromName',
    'toName',
])

<div class="filter-group filter-date-range" data-filter-date-range>
    <label for="{{ $id }}-preset" class="filter-label">{{ $label }}</label>

    <select
        id="{{ $id }}-preset"
        class="panel-input w-full text-sm"
        data-date-preset-select
        aria-label="{{ $label }} options"
    >
        <option value="">Any time</option>
        <option value="today">Today</option>
        <option value="yesterday">Yesterday</option>
        <option value="this_week">This Week</option>
        <option value="last_week">Last Week</option>
        <option value="this_month">This Month</option>
        <option value="last_month">Last Month</option>
        <option value="this_quarter">This Quarter</option>
        <option value="last_quarter">Last Quarter</option>
        <option value="this_year">This Year</option>
        <option value="last_year">Last Year</option>
        <option value="custom">Custom Range</option>
    </select>

    <div class="filter-custom-range" data-custom-range hidden>
        <div class="filter-date-grid">
            <div class="filter-date-field">
                <label for="{{ $id }}-from" class="filter-custom-range__label">From</label>
                <x-date-time-picker
                    name="{{ $fromName }}"
                    id="{{ $id }}-from"
                    mode="date"
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
