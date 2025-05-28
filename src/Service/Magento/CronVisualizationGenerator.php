<?php

declare(strict_types=1);

namespace App\Service\Magento;

use DateTimeImmutable;

class CronVisualizationGenerator
{
    private const DAYS_IN_YEAR = 365;
    private const MINUTES_IN_HOUR = 60;
    private const MINUTES_IN_DAY = 1440;

    private string $templatePath;

    public function __construct(?string $templatePath = null)
    {
        $this->templatePath = $templatePath ?: __DIR__ . '/../../../templates/cron';
    }

    /**
     * Generate heatmap with minute-level detail
     *
     * @param array $cronGroups Organized cron data by group
     * @param int $year Year for heatmap
     * @return string HTML content with heatmap
     */
    public function generateHeatmap(array $cronGroups, int $year): string
    {
        $matrix = $this->createMinuteMatrix($cronGroups, $year);
        $templateData = [
            'year' => $year,
            'matrix' => $matrix,
            'groupMatrix' => $this->createGroupMatrices($cronGroups, $year),
            'maxValue' => $this->findMaxValue($matrix),
            'groups' => array_keys($cronGroups),
        ];

        return $this->renderTemplate('heatmap.phtml', $templateData);
    }

    /**
     * Create minute-level execution matrix for all jobs
     *
     * @param array $cronGroups Organized cron data by group
     * @param int $year Year for matrix
     * @return array Minute-level execution matrix
     */
    private function createMinuteMatrix(array $cronGroups, int $year): array
    {
        $occurencesPerTimestampSecond = [];

        foreach ($cronGroups as $groupName => $jobs) {
            foreach ($jobs as $jobName => $jobData) {
                foreach ($jobData['execution_times'] as $timestamps) {
                    foreach ($timestamps as $timestamp) {
                        if (!isset($occurencesPerTimestampSecond[$timestamp])) {
                            $occurencesPerTimestampSecond[$timestamp] = 0;
                        }
                        $occurencesPerTimestampSecond[$timestamp]++;
                    }
                }
            }
        }

        $matrix = [];
        for ($day = 0; $day < self::DAYS_IN_YEAR; $day++) {
            $matrix[$day] = array_fill(0, self::MINUTES_IN_DAY, 0);
        }

        foreach ($occurencesPerTimestampSecond as $timestamp => $count) {
            $date = new DateTimeImmutable('@' . $timestamp);
            $dayOfYear = (int)$date->format('z');
            $hour = (int)$date->format('H');
            $minute = (int)$date->format('i');
            $minuteOfDay = ($hour * self::MINUTES_IN_HOUR) + $minute;

            if (!isset($matrix[$dayOfYear][$minuteOfDay])) {
                $matrix[$dayOfYear][$minuteOfDay] = 0;
            }

            $matrix[$dayOfYear][$minuteOfDay] += $count;
        }

        return $matrix;
    }

    /**
     * Create group-specific matrices
     *
     * @param array $cronGroups Organized cron data by group
     * @param int $year Year for matrices
     * @return array Group-specific matrices
     */
    private function createGroupMatrices(array $cronGroups, int $year): array
    {
        // Create matrix for 365 days x 1440 minutes
        $occurencesPerTimestampSecondByGroup = [];

        foreach ($cronGroups as $groupName => $jobs) {
            $occurencesPerTimestampSecond = [];
            foreach ($jobs as $jobName => $jobData) {
                foreach ($jobData['execution_times'] as $timestamps) {
                    foreach ($timestamps as $timestamp) {
                        if (!isset($occurencesPerTimestampSecond[$timestamp])) {
                            $occurencesPerTimestampSecond[$timestamp] = 0;
                        }
                        $occurencesPerTimestampSecond[$timestamp]++;
                    }
                }
            }
            $occurencesPerTimestampSecondByGroup[$groupName] = $occurencesPerTimestampSecond;
        }


        $groupMatrices = [];
        foreach ($occurencesPerTimestampSecondByGroup as $groupName => $occurencesPerTimestampSecond) {
            $matrix = [];
            for ($day = 0; $day < 365; $day++) {
                $matrix[$day] = array_fill(0, 1440, 0);
            }

            foreach ($occurencesPerTimestampSecond as $timestamp => $count) {
                $date = new DateTimeImmutable('@' . $timestamp);
                $dayOfYear = (int)$date->format('z');
                $hour = (int)$date->format('H');
                $minute = (int)$date->format('i');
                $minuteOfDay = ($hour * self::MINUTES_IN_HOUR) + $minute;

                if (!isset($matrix[$dayOfYear][$minuteOfDay])) {
                    $matrix[$dayOfYear][$minuteOfDay] = 0;
                }

                $matrix[$dayOfYear][$minuteOfDay] += $count;
            }

            $groupMatrices[$groupName] = $matrix;
        }

        return $groupMatrices;
    }

    /**
     * Find maximum value for color scaling
     *
     * @param array $matrix Execution matrix
     * @return int Maximum execution count
     */
    private function findMaxValue(array $matrix): int
    {
        $maxValue = 0;
        foreach ($matrix as $dayData) {
            $dayMax = max($dayData);
            if ($dayMax > $maxValue) {
                $maxValue = $dayMax;
            }
        }
        return $maxValue;
    }

    /**
     * Render a template file with data
     *
     * @param string $templateName Template filename
     * @param array $data Data to pass to template
     * @return string Rendered HTML
     */
    private function renderTemplate(string $templateName, array $data): string
    {
        $templateFile = $this->templatePath . '/' . $templateName;

        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template not found: {$templateFile}");
        }

        // Extract data to make variables available in template
        extract($data);

        // Use output buffering to capture template output
        ob_start();
        include $templateFile;
        $content = ob_get_clean();

        return $content ?: '';
    }
}
