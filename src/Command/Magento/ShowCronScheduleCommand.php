<?php

declare(strict_types=1);

namespace App\Command\Magento;

use App\Service\CronAnalyser;
use App\Service\FileManager;
use App\Service\Magento\CronVisualizationGenerator;
use App\Service\Magento\FileScanner;
use App\Service\Magento\Parser\XmlParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'magento:cron:schedule:heatmap', description: 'Show cron schedule as heatmap')]
class ShowCronScheduleCommand extends Command
{
    public function __construct(
        private readonly FileScanner $fileScanner,
        private readonly XmlParser $xmlParser,
        private readonly CronAnalyser $cronAnalyser,
        private readonly FileManager $fileManager,
        private readonly CronVisualizationGenerator $cronVisualizationGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('path', 'p', InputArgument::OPTIONAL, 'Path to Magento installation', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // TODO: getcwd is a system call, refactor it outside of logic
        $magentoLocation = $input->getOption('path') ?: getcwd();

        $files = $this->fileScanner->findCrontabFiles($magentoLocation);
        $cronGroups = [];
        foreach ($files as $file) {
            $cronGroups = array_merge_recursive($cronGroups, $this->xmlParser->getInfoAboutCronJobsInXmlFile($file));
        }

        foreach ($cronGroups as &$cronGroup) {
            $cronGroup = $this->cronAnalyser->generateExecutionTimesForGroup($cronGroup);
        }

        $content = $this->cronVisualizationGenerator->generateHeatmap($cronGroups, 2025);
        if (!$this->fileManager->saveTextToFile($content, __DIR__ . '/../../../var/cron_heatmap.html')) {
            return Command::FAILURE;
        };

        return Command::SUCCESS;
    }
}
