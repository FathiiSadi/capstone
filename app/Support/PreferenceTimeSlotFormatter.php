<?php

namespace App\Support;

use Carbon\Carbon;

class PreferenceTimeSlotFormatter
{
    public const DAY_PAIR_MAP = [
        'Sat/Tue' => ['Saturday', 'Tuesday'],
        'Tue/Sat' => ['Tuesday', 'Saturday'],
        'Sun/Wed' => ['Sunday', 'Wednesday'],
        'Wed/Sun' => ['Sunday', 'Wednesday'],
        'Mon/Thu' => ['Monday', 'Thursday'],
        'Thu/Mon' => ['Monday', 'Thursday'],
    ];

    public const TIME_BLOCKS = [
        'Morning' => ['start' => '08:30:00', 'end' => '11:30:00'],
        'Noon' => ['start' => '11:30:00', 'end' => '14:30:00'],
        'Afternoon' => ['start' => '14:30:00', 'end' => '17:30:00'],
    ];

    /**
     * Normalize a day selection (string/array/json) into an array of full day names.
     */
    public static function normalizeDaysValue(array|string|null $value): ?array
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return self::normalizeDayArray($value);
        }

        $trimmed = trim($value);

        if (str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return self::normalizeDayArray($decoded);
            }
        }

        if (isset(self::DAY_PAIR_MAP[$trimmed])) {
            return self::DAY_PAIR_MAP[$trimmed];
        }

        if (str_contains($trimmed, '/')) {
            return self::normalizeDayArray(explode('/', $trimmed));
        }

        return [self::normalizeDayName($trimmed)];
    }

    /**
     * Normalize a time selection (label/json/time string) into [start, end].
     */
    public static function normalizeTimeValue(array|string|null $value): array
    {
        if (is_array($value)) {
            $flattened = array_filter($value);
            if (empty($flattened)) {
                return ['start' => null, 'end' => null];
            }
            $value = $flattened[0];
        }

        if (is_null($value) || $value === '') {
            return ['start' => null, 'end' => null];
        }

        $trimmed = trim($value);

        if (isset(self::TIME_BLOCKS[$trimmed])) {
            return self::TIME_BLOCKS[$trimmed];
        }

        if (str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded) && !empty($decoded)) {
                $start = $decoded[0];
                return [
                    'start' => $start,
                    'end' => self::calculateEndFromStart($start),
                ];
            }
        }

        return [
            'start' => $trimmed,
            'end' => self::calculateEndFromStart($trimmed),
        ];
    }

    /**
     * Best-effort guess for human-readable label based on the stored start time.
     */
    public static function resolveLabelFromStart(?string $startTime): ?string
    {
        if (!$startTime) {
            return null;
        }

        foreach (self::TIME_BLOCKS as $label => $range) {
            if ($range['start'] === $startTime) {
                return $label;
            }
        }

        return null;
    }

    /**
     * Calculate the end time of a preference slot. Default window is 3 hours (two sessions).
     */
    public static function calculateEndFromStart(?string $startTime, int $minutes = 180): ?string
    {
        if (!$startTime) {
            return null;
        }

        return Carbon::parse($startTime)
            ->addMinutes($minutes)
            ->format('H:i:s');
    }

    /**
     * Normalize stored DB value (string/array) into JSON-ready array.
     */
    protected static function normalizeDayArray(array $days): array
    {
        $normalized = collect($days)
            ->flatMap(function ($day) {
                if (is_null($day) || $day === '') {
                    return [];
                }

                $day = trim($day);

                if (isset(self::DAY_PAIR_MAP[$day])) {
                    return self::DAY_PAIR_MAP[$day];
                }

                if (str_contains($day, '/')) {
                    return self::normalizeDayArray(explode('/', $day));
                }

                return [self::normalizeDayName($day)];
            })
            ->map(fn($day) => self::normalizeDayName($day))
            ->unique()
            ->values()
            ->all();

        return $normalized;
    }

    protected static function normalizeDayName(string $value): string
    {
        $map = [
            'Sun' => 'Sunday',
            'Mon' => 'Monday',
            'Tue' => 'Tuesday',
            'Wed' => 'Wednesday',
            'Thu' => 'Thursday',
            'Sat' => 'Saturday',
        ];

        $trimmed = ucfirst(strtolower(trim($value)));

        return $map[$trimmed] ?? $trimmed;
    }
}
