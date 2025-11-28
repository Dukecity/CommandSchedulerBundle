<?php

namespace App\Tests\App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Custom kernel for integration tests with TestCustomScheduledCommand configured.
 * Uses a custom bundle that skips default entity mapping to avoid conflicts.
 */
class AppKernelCustomEntity extends Kernel
{
    public function __construct()
    {
        parent::__construct('test_custom_entity', true);
    }

    public function registerBundles(): array
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            // Use custom bundle that skips entity mapping
            new DukecityCommandSchedulerBundleCustomEntity(),
            new \Liip\TestFixturesBundle\LiipTestFixturesBundle(),
            new \Symfony\Bundle\DebugBundle\DebugBundle(),
            new \Knp\Bundle\TimeBundle\KnpTimeBundle(),
        ];
    }

    /**
     * @throws \Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config/config_custom_entity.yml');
    }

    public function getCacheDir(): string
    {
        return __DIR__.'/../../build/cache/test_custom_entity';
    }

    public function getLogDir(): string
    {
        return __DIR__.'/../../build/kernel_logs/test_custom_entity';
    }
}
