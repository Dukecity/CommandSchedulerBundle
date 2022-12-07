<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace App\Tests\App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class AppKernel.
 */
class AppKernel extends Kernel
{
    #use MicroKernelTrait;

    public function __construct()
    {
        parent::__construct('test', true);
    }

    /**
     * Register Bundles for test-configuration.
     */
    public function registerBundles(): array
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new \Dukecity\CommandSchedulerBundle\DukecityCommandSchedulerBundle(),
            new \Liip\TestFixturesBundle\LiipTestFixturesBundle(),
            #\Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true, 'test' => true],
            new \Symfony\Bundle\DebugBundle\DebugBundle(),
            //new \Symfony\Bundle\WebProfilerBundle\WebProfilerBundle(),
            //new \DAMA\DoctrineTestBundle\DAMADoctrineTestBundle::class => ['test' => true],
        ];
    }

    /**
     * @throws \Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }

    public function getCacheDir(): string
    {
        return __DIR__.'/../../build/cache/'.$this->getEnvironment();
    }

    public function getLogDir(): string
    {
        return __DIR__.'/../../build/kernel_logs/'.$this->getEnvironment();
    }
}
