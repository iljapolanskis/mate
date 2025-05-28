<?php
declare(strict_types=1);

namespace App\Service;

class CronAnalyser
{
    public function __construct(
        private DateTimeManager $dateTimeManager,
        private CronExpressionFactory $cronExpressionFactory,
    ) {
    }
    /**
     * Generate all execution times for a cron expression within a year
     *
     * @param string $cronExpression Cron expression (e.g., "0 2 * * *")
     * @param int $year Year to generate schedule for
     * @return array Array of DateTimeImmutable objects representing execution times
     */
    public function generateExecutionTimes(string $cronExpression, int $year): array
    {
        if ($cronExpression === '* * * * *') {
            $startDate = $this->dateTimeManager->getDate("{$year}-01-01 00:00:00");
            $executionTimes = [];
            for ($i = 0; $i < 60 * 24 * 365; $i++) {
                $executionTimes[] = $startDate->getTimestamp() + $i * 60;
            }
            return $executionTimes;
        }

        try {
            $cron = $this->cronExpressionFactory->createFromExpression($cronExpression);
        } catch (\Throwable) {
            return [];
        }

        $startDate = $this->dateTimeManager->getDate("{$year}-01-01 00:00:00");
        $endDate = $this->dateTimeManager->getDate("{$year}-12-31 23:59:59");

        $executionTimes = [];

        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            $nextRun = $cron->getNextRunDate($currentDate);

            if ($nextRun > $endDate) {
                break;
            }

            if ((int)$nextRun->format('Y') === $year) {
                $executionTimes[] = $nextRun->getTimestamp();
            }

            $currentDate = $this->dateTimeManager->addMinutes($currentDate, 1);
        }

        return $executionTimes;
    }

    public function generateExecutionTimesForGroup(array $cronGroup): array
    {
        foreach ($cronGroup as &$jobInfo) {
            if (is_array($jobInfo['handler'])) {
                $jobInfo['handler'] = reset($jobInfo['handler']); // TODO: Properly resolve it in XmlParser itself
            }
            if (is_array($jobInfo['cron_expr'])) {
                $jobInfo['cron_expr'] = reset($jobInfo['cron_expr']); // TODO: Properly resolve it in XmlParser itself
            }
            $currentYear = (int)date('Y');
            $dots[] = $this->generateExecutionTimes($jobInfo['cron_expr'], $currentYear);
            $jobInfo['execution_times'] = $dots;
        }

        return $cronGroup;
    }
}
