<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class DateRangeFilter
{
    /**
     * Apply an inclusive calendar-date range without wrapping the indexed column
     * in a database DATE() function.
     */
    public static function apply(object $query, string $column, mixed $from, mixed $to): void
    {
        $fromDate = self::parse($from, 'from');
        $toDate = self::parse($to, 'to');

        if ($fromDate && $toDate && $fromDate->greaterThan($toDate)) {
            throw ValidationException::withMessages([
                'filters' => 'The To date must be greater than or equal to the From date.',
            ]);
        }

        if ($fromDate) {
            $query->where($column, '>=', $fromDate->startOfDay());
        }

        if ($toDate) {
            $query->where($column, '<', $toDate->addDay()->startOfDay());
        }
    }

    private static function parse(mixed $value, string $boundary): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw ValidationException::withMessages([
                'filters' => "The {$boundary} date must use the YYYY-MM-DD format.",
            ]);
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, config('app.timezone'));
        $errors = CarbonImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw ValidationException::withMessages([
                'filters' => "The {$boundary} date is invalid.",
            ]);
        }

        return $date;
    }
}
