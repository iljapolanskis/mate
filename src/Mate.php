<?php
declare(strict_types=1);

namespace App;

use App\Command\File\FileEditCommand;
use App\Command\Magento\GetAllEncryptedConfigValues;
use App\Command\Magento\ShowCronScheduleCommand;
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Console\Command\Command;


class Mate extends Application
{
    public const NAME = 'Mate';
    public const VERSION = '1.0.0';

    /** @var class-string[] */
    private array $commands = [
        ShowCronScheduleCommand::class,
        GetAllEncryptedConfigValues::class,
        FileEditCommand::class,
    ];

    private ContainerInterface $container;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct(
            self::NAME,
            self::VERSION
        );

        $this->setupContainer();
        $this->registerCommands();
    }

    /**
     * Sets up the dependency injection container.
     *
     * @return void
     * @throws Exception
     */
    private function setupContainer(): void
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../config'));
        $loader->load('services.yaml');

        $container->compile();
        $this->container = $container;
    }

    /**
     * Registers all available commands with the application.
     *
     * @return void
     * @throws Exception
     */
    private function registerCommands(): void
    {
        foreach ($this->commands as $command) {
            $command = $this->container->get($command);
            if (!($command instanceof Command)) {
                throw new Exception(sprintf('Command "%s" is not an instance of "%s".', $command, Command::class));
            }

            $this->add($command);
        }
    }

    /**
     * Gets the dependency injection container.
     *
     * @return ContainerInterface The container instance
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
