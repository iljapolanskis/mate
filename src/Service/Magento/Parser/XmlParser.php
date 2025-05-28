<?php
declare(strict_types=1);

namespace App\Service\Magento\Parser;

use App\Service\CronAnalyser;
use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

class XmlParser
{
    /**
     * TODO: Add Module Loading order to support overrides
     * @return array<string, array<string, array{handler: string, cron_expr: string}>>
     */
    public function getInfoAboutCronJobsInXmlFile(string $pathToFile): array
    {
        $fileContents = file_get_contents($pathToFile);
        $crawler = new Crawler($fileContents);

        $groups = $crawler->filterXPath('//config/group');

        $jobsByGroups = [];

        /** @var DOMElement $group */
        foreach ($groups as $group) {
            /** @var DOMElement $job */
            foreach ($group->childNodes as $job) {
                if ($job->nodeName !== 'job') {
                    continue;
                }

                $jobsByGroups[$group->getAttribute('id')][$job->getAttribute('name')] = [
                    'handler' => $job->getAttribute('instance') . ':' . $job->getAttribute('method'),
                    'cron_expr' => $job->getElementsByTagName('schedule')->item(0)->nodeValue ?? '* * * * *',
                ];
            }
        }

        return $jobsByGroups;
    }
}
