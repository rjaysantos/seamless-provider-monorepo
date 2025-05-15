<?php

namespace Providers\Hg5;

use Carbon\Carbon;

class Hg5DateTime
{
    public static function getDateTimeNow(): string
    {
        $datetime = Carbon::now('GMT+8')->setTimezone('-04:00');

        // Convert microseconds (6) to nanoseconds (9)
        $nanoseconds = (int) ($datetime->micro * 1000);

        return sprintf(
            "%s.%09d%s",
            $datetime->format('Y-m-d\TH:i:s'),
            $nanoseconds,
            $datetime->format('P')
        );
    }
}