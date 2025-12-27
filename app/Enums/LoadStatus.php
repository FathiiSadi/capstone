<?php

namespace App\Enums;

enum LoadStatus: string
{
    case OK = 'ok';
    case UNDER_MINIMUM = 'under_minimum';
    case OVER_LOADED = 'over_loaded';

    public function label(): string
    {
        return match ($this) {
            self::OK => 'OK',
            self::UNDER_MINIMUM => 'Under Minimum',
            self::OVER_LOADED => 'Over Loaded',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OK => 'Meets minimum credit hour requirements',
            self::UNDER_MINIMUM => 'Below minimum required credit hours - requires admin intervention',
            self::OVER_LOADED => 'Exceeds maximum recommended credit hours',
        };
    }
}
