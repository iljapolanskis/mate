<?php
declare(strict_types=1);

namespace App\Command\Magento;

use App\Service\FileManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('magento:config:get:encrypted', 'Get all encrypted config values')]
class GetAllEncryptedConfigValues extends Command
{
    public function __construct(
//        private readonly FileManager $fileManager,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->addOption('path', 'p', InputArgument::OPTIONAL, 'Path to Magento installation', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // TODO: getcwd is a system call, refactor it outside of logic
        $magentoLocation = $input->getOption('path') ?: getcwd();

        $env = require $magentoLocation . '/app/etc/env.php';
        $cryptKey= $env['crypt']['key'] ?? null;

        var_dump($cryptKey);

        return Command::SUCCESS;
    }
}
