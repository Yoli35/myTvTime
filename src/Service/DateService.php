<?php

namespace App\Service;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use IntlDateFormatter;

class DateService
{
    public function newDate($dateString, $timeZone, $allDay = false): DateTime
    {
        try {
            $date = new DateTime($dateString, new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTime();
        }
        if ($allDay) $date->setTime(0, 0);

        return $date;
    }

    public function newDateImmutable($dateString, $timeZone, $allDay = false): DateTimeImmutable
    {
        try {
            $date = new DateTimeImmutable($dateString, new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTimeImmutable();
        }
        if (!$allDay) $date->setTime(0, 0);

        return $date;
    }

    public function newDateFromTimestamp($timestamp, $timeZone, $allDay = false): DateTime
    {
        try {
            $date = new DateTime();
            $date->setTimestamp($timestamp);
            $date->setTimezone(new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTime();
        }
        if ($allDay) $date->setTime(0, 0);

        return $date;
    }

    public function newDateImmutableFromTimestamp($timestamp, $timeZone, $allDay = false): DateTimeImmutable
    {
        try {
            $date = new DateTimeImmutable();
            $date->setTimestamp($timestamp);
            $date->setTimezone(new DateTimeZone($timeZone));
        } catch (Exception) {
            $date = new DateTimeImmutable();
        }
        if ($allDay) $date->setTime(0, 0);

        return $date;
    }

    public function formatDate($dateSting, $timeZone, $locale): string
    {
        $format = datefmt_create($locale, IntlDateFormatter::SHORT, IntlDateFormatter::NONE, $timeZone, IntlDateFormatter::GREGORIAN);
        return datefmt_format($format, $dateSting);
    }
}