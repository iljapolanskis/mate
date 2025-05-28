<?php
declare(strict_types=1);

namespace App\Service;

use DateInterval;
use DateTimeImmutable;

class DateTimeManager
{
    public function getDate(string $datetime): DateTimeImmutable
    {
        return new DateTimeImmutable($datetime);
    }

    public function getCurrentDate(): DateTimeImmutable
    {

    }

    public function addMinutes(DateTimeImmutable $date, int $minutes): DateTimeImmutable
    {
        return $date->add(new DateInterval("PT{$minutes}M"));
    }
}
