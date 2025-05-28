<?php
declare(strict_types=1);

namespace App\Service;

use Cron\CronExpression;

class CronExpressionFactory
{
    public function createFromExpression(string $cronExpression): CronExpression
    {
        return new CronExpression($cronExpression);
    }
}
