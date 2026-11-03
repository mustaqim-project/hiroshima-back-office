<?php

namespace App\Models\Concerns;

use Carbon\Carbon;
use DateTimeInterface;

trait SerializesWibDates
{
    /**
     * Prepare a date for array / JSON serialization in Asia/Jakarta (WIB / UTC+7).
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return Carbon::instance($date)->setTimezone('Asia/Jakarta')->toIso8601String();
    }
}
